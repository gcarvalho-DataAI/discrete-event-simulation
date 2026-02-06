#!/usr/bin/env python3
import argparse
import csv
import json
import os
from datetime import datetime
from pathlib import Path
from typing import Dict, List

import sys

ROOT_DIR = Path(__file__).resolve().parents[1]
SRC_DIR = ROOT_DIR / "src"
sys.path.insert(0, str(SRC_DIR))

from cafe_sim import CafeSimulation, CustomerType, SimConfig, summarize_metrics


def load_instance(path: str) -> Dict:
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def run_instance(instance_path: str, seed: int) -> Dict:
    inst = load_instance(instance_path)

    config = SimConfig(
        hours=inst.get("hours", 4.0),
        warmup_hours=inst.get("warmup_hours", 0.5),
        order_attendants=inst.get("staffing", {}).get("order_attendants", 1),
        baristas=inst.get("staffing", {}).get("baristas", 2),
        pickup_attendants=inst.get("staffing", {}).get("pickup_attendants", 1),
        prep_queue_alpha=inst.get("prep_queue_alpha", 0.10),
    )

    arrivals = inst.get("arrival_rates_per_hour", {})
    order_means = inst.get("order_time_mean", {})
    prep_means = inst.get("prep_time_mean", {})
    patience_means = inst.get("patience_mean", {})

    customer_types = [
        CustomerType("fast", arrivals.get("fast", 20.0), order_means.get("fast", 0.8),
                     prep_means.get("fast", 1.0), patience_means.get("fast", 3.0)),
        CustomerType("medium", arrivals.get("medium", 12.0), order_means.get("medium", 1.2),
                     prep_means.get("medium", 2.5), patience_means.get("medium", 6.0)),
        CustomerType("slow", arrivals.get("slow", 6.0), order_means.get("slow", 1.8),
                     prep_means.get("slow", 4.0), patience_means.get("slow", 10.0)),
    ]

    sim = CafeSimulation(config, customer_types, seed=seed)
    metrics = sim.run()
    summary = summarize_metrics(metrics, config)
    summary.update({
        "instance": os.path.basename(instance_path),
        "source": inst.get("source", ""),
        "peak_hour": inst.get("peak_hour", ""),
        "seed": seed,
    })
    return summary


def write_csv(rows: List[Dict], output_path: str) -> None:
    if not rows:
        return
    fieldnames = list(rows[0].keys())
    with open(output_path, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)


def main() -> None:
    parser = argparse.ArgumentParser(description="Run batch simulations for all instances")
    parser.add_argument("--instances-dir", default="data/instances")
    parser.add_argument("--output", default="output/metrics.csv")
    parser.add_argument("--seed", type=int, default=42)
    args = parser.parse_args()

    instances_dir = Path(args.instances_dir)
    instance_files = sorted(p for p in instances_dir.glob("*.json") if p.name != "summary.json")
    rows = []

    for path in instance_files:
        rows.append(run_instance(str(path), args.seed))

    os.makedirs(os.path.dirname(args.output), exist_ok=True)
    write_csv(rows, args.output)
    print(f"Wrote {args.output}")


if __name__ == "__main__":
    main()
