import argparse
import random
import statistics
from dataclasses import dataclass
from typing import Dict, List, Tuple

import simpy


@dataclass
class CustomerType:
    name: str
    arrival_rate_per_hour: float
    order_time_mean: float
    prep_time_mean: float
    patience_mean: float


@dataclass
class SimConfig:
    hours: float = 4.0
    warmup_hours: float = 0.5
    order_attendants: int = 1
    baristas: int = 2
    pickup_attendants: int = 1
    prep_queue_alpha: float = 0.10
    pickup_time_mean: float = 0.2  # minutes
    sample_interval: float = 0.5  # minutes


@dataclass
class Metrics:
    total_customers: int = 0
    completed_customers: int = 0
    abandoned_customers: int = 0
    order_waits: List[float] = None
    prep_waits: List[float] = None
    pickup_waits: List[float] = None
    system_times: List[float] = None
    order_busy_time: float = 0.0
    prep_busy_time: float = 0.0
    pickup_busy_time: float = 0.0
    order_queue_samples: List[int] = None
    prep_queue_samples: List[int] = None
    pickup_queue_samples: List[int] = None

    def __post_init__(self):
        self.order_waits = []
        self.prep_waits = []
        self.pickup_waits = []
        self.system_times = []
        self.order_queue_samples = []
        self.prep_queue_samples = []
        self.pickup_queue_samples = []


class CafeSimulation:
    def __init__(self, config: SimConfig, customer_types: List[CustomerType], seed: int = 42):
        self.config = config
        self.customer_types = customer_types
        self.seed = seed
        self.env = simpy.Environment()
        self.metrics = Metrics()
        self.order_counter = simpy.Resource(self.env, capacity=config.order_attendants)
        self.prep_area = simpy.Resource(self.env, capacity=config.baristas)
        self.pickup_counter = simpy.Resource(self.env, capacity=config.pickup_attendants)
        random.seed(seed)

    def expovariate_minutes(self, rate_per_hour: float) -> float:
        if rate_per_hour <= 0:
            return float("inf")
        rate_per_min = rate_per_hour / 60.0
        return random.expovariate(rate_per_min)

    def triangular_mean(self, mean: float, spread: float = 0.3) -> float:
        low = mean * (1 - spread)
        high = mean * (1 + spread)
        return random.triangular(low, high, mean)

    def patience_time(self, mean: float) -> float:
        return random.expovariate(1.0 / mean)

    def arrival_process(self, cust_type: CustomerType):
        if cust_type.arrival_rate_per_hour <= 0:
            return
        i = 0
        while True:
            interarrival = self.expovariate_minutes(cust_type.arrival_rate_per_hour)
            yield self.env.timeout(interarrival)
            i += 1
            name = f"{cust_type.name}-{i}"
            self.env.process(self.customer_process(name, cust_type))

    def customer_process(self, name: str, cust_type: CustomerType):
        arrival_time = self.env.now
        self.metrics.total_customers += 1

        # Order queue with reneging
        patience = self.patience_time(cust_type.patience_mean)
        with self.order_counter.request() as req:
            results = yield req | self.env.timeout(patience)
            if req not in results:
                self.metrics.abandoned_customers += 1
                return
            order_wait = self.env.now - arrival_time
            order_time = self.triangular_mean(cust_type.order_time_mean)
            yield self.env.timeout(order_time)
            self.metrics.order_busy_time += order_time

        # Preparation queue (state-dependent time)
        prep_arrival = self.env.now
        with self.prep_area.request() as req_prep:
            yield req_prep
            prep_wait = self.env.now - prep_arrival
            queue_factor = 1.0 + (len(self.prep_area.queue) * self.config.prep_queue_alpha)
            prep_time = self.triangular_mean(cust_type.prep_time_mean) * queue_factor
            yield self.env.timeout(prep_time)
            self.metrics.prep_busy_time += prep_time

        # Pickup queue
        pickup_arrival = self.env.now
        with self.pickup_counter.request() as req_pickup:
            yield req_pickup
            pickup_wait = self.env.now - pickup_arrival
            pickup_time = self.triangular_mean(self.config.pickup_time_mean, spread=0.5)
            yield self.env.timeout(pickup_time)
            self.metrics.pickup_busy_time += pickup_time

        depart_time = self.env.now
        if depart_time >= self.config.warmup_hours * 60:
            self.metrics.completed_customers += 1
            self.metrics.order_waits.append(order_wait)
            self.metrics.prep_waits.append(prep_wait)
            self.metrics.pickup_waits.append(pickup_wait)
            self.metrics.system_times.append(depart_time - arrival_time)

    def sampler(self):
        while True:
            yield self.env.timeout(self.config.sample_interval)
            if self.env.now >= self.config.warmup_hours * 60:
                self.metrics.order_queue_samples.append(len(self.order_counter.queue))
                self.metrics.prep_queue_samples.append(len(self.prep_area.queue))
                self.metrics.pickup_queue_samples.append(len(self.pickup_counter.queue))

    def run(self):
        for ct in self.customer_types:
            self.env.process(self.arrival_process(ct))
        self.env.process(self.sampler())
        self.env.run(until=self.config.hours * 60)
        return self.metrics


def summarize_metrics(metrics: Metrics, config: SimConfig) -> Dict[str, float]:
    def safe_mean(values: List[float]) -> float:
        return statistics.mean(values) if values else 0.0

    sim_time = (config.hours - config.warmup_hours) * 60.0
    return {
        "completed_customers": metrics.completed_customers,
        "abandoned_customers": metrics.abandoned_customers,
        "abandonment_rate": metrics.abandoned_customers / max(metrics.total_customers, 1),
        "avg_order_wait": safe_mean(metrics.order_waits),
        "avg_prep_wait": safe_mean(metrics.prep_waits),
        "avg_pickup_wait": safe_mean(metrics.pickup_waits),
        "avg_system_time": safe_mean(metrics.system_times),
        "order_utilization": metrics.order_busy_time / max(sim_time * config.order_attendants, 1e-6),
        "prep_utilization": metrics.prep_busy_time / max(sim_time * config.baristas, 1e-6),
        "pickup_utilization": metrics.pickup_busy_time / max(sim_time * config.pickup_attendants, 1e-6),
        "avg_order_queue": safe_mean(metrics.order_queue_samples),
        "avg_prep_queue": safe_mean(metrics.prep_queue_samples),
        "avg_pickup_queue": safe_mean(metrics.pickup_queue_samples),
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Discrete Event Simulation for a small cafe")
    parser.add_argument("--instance", type=str, default="", help="Path to instance JSON file")
    parser.add_argument("--seed", type=int, default=42)
    parser.add_argument("--hours", type=float, default=4.0)
    parser.add_argument("--warmup", type=float, default=0.5)
    parser.add_argument("--order-attendants", type=int, default=1)
    parser.add_argument("--baristas", type=int, default=2)
    parser.add_argument("--pickup-attendants", type=int, default=1)
    parser.add_argument("--prep-queue-alpha", type=float, default=0.10)
    parser.add_argument("--arrival-fast", type=float, default=20.0)
    parser.add_argument("--arrival-medium", type=float, default=12.0)
    parser.add_argument("--arrival-slow", type=float, default=6.0)
    parser.add_argument("--order-mean-fast", type=float, default=0.8)
    parser.add_argument("--order-mean-medium", type=float, default=1.2)
    parser.add_argument("--order-mean-slow", type=float, default=1.8)
    parser.add_argument("--prep-mean-fast", type=float, default=1.0)
    parser.add_argument("--prep-mean-medium", type=float, default=2.5)
    parser.add_argument("--prep-mean-slow", type=float, default=4.0)
    parser.add_argument("--patience-fast", type=float, default=3.0)
    parser.add_argument("--patience-medium", type=float, default=6.0)
    parser.add_argument("--patience-slow", type=float, default=10.0)
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    if args.instance:
        import json
        with open(args.instance, "r", encoding="utf-8") as f:
            inst = json.load(f)
        args.hours = inst.get("hours", args.hours)
        args.warmup = inst.get("warmup_hours", args.warmup)
        staff = inst.get("staffing", {})
        args.order_attendants = staff.get("order_attendants", args.order_attendants)
        args.baristas = staff.get("baristas", args.baristas)
        args.pickup_attendants = staff.get("pickup_attendants", args.pickup_attendants)
        args.prep_queue_alpha = inst.get("prep_queue_alpha", args.prep_queue_alpha)
        arrivals = inst.get("arrival_rates_per_hour", {})
        args.arrival_fast = arrivals.get("fast", args.arrival_fast)
        args.arrival_medium = arrivals.get("medium", args.arrival_medium)
        args.arrival_slow = arrivals.get("slow", args.arrival_slow)
        order_means = inst.get("order_time_mean", {})
        args.order_mean_fast = order_means.get("fast", args.order_mean_fast)
        args.order_mean_medium = order_means.get("medium", args.order_mean_medium)
        args.order_mean_slow = order_means.get("slow", args.order_mean_slow)
        prep_means = inst.get("prep_time_mean", {})
        args.prep_mean_fast = prep_means.get("fast", args.prep_mean_fast)
        args.prep_mean_medium = prep_means.get("medium", args.prep_mean_medium)
        args.prep_mean_slow = prep_means.get("slow", args.prep_mean_slow)
        patience_means = inst.get("patience_mean", {})
        args.patience_fast = patience_means.get("fast", args.patience_fast)
        args.patience_medium = patience_means.get("medium", args.patience_medium)
        args.patience_slow = patience_means.get("slow", args.patience_slow)
    config = SimConfig(
        hours=args.hours,
        warmup_hours=args.warmup,
        order_attendants=args.order_attendants,
        baristas=args.baristas,
        pickup_attendants=args.pickup_attendants,
        prep_queue_alpha=args.prep_queue_alpha,
    )
    customer_types = [
        CustomerType("fast", args.arrival_fast, args.order_mean_fast, args.prep_mean_fast, args.patience_fast),
        CustomerType("medium", args.arrival_medium, args.order_mean_medium, args.prep_mean_medium, args.patience_medium),
        CustomerType("slow", args.arrival_slow, args.order_mean_slow, args.prep_mean_slow, args.patience_slow),
    ]
    sim = CafeSimulation(config, customer_types, seed=args.seed)
    metrics = sim.run()
    summary = summarize_metrics(metrics, config)

    print("Simulation Summary")
    for k, v in summary.items():
        if isinstance(v, float):
            print(f"{k}: {v:.3f}")
        else:
            print(f"{k}: {v}")


if __name__ == "__main__":
    main()
