#!/usr/bin/env python3
import argparse
import csv
import os
from typing import Dict, List, Tuple


def read_csv(path: str) -> List[Dict[str, str]]:
    with open(path, newline="", encoding="utf-8") as f:
        return list(csv.DictReader(f))


def score(row: Dict[str, str]) -> float:
    # Lower is better. Weights emphasize abandonment and system time.
    abandonment = float(row.get("abandonment_rate", 0))
    system_time = float(row.get("avg_system_time", 0))
    order_wait = float(row.get("avg_order_wait", 0))
    prep_wait = float(row.get("avg_prep_wait", 0))
    pickup_wait = float(row.get("avg_pickup_wait", 0))
    return (
        abandonment * 100.0
        + system_time * 2.0
        + order_wait * 1.0
        + prep_wait * 1.0
        + pickup_wait * 0.5
    )


def main() -> None:
    parser = argparse.ArgumentParser(description="Top-10 scenario report")
    parser.add_argument("--input", default="output/metrics.csv")
    parser.add_argument("--output", default="output/top10.csv")
    parser.add_argument("--source", default="", help="Optional source filter")
    args = parser.parse_args()

    rows = read_csv(args.input)
    if args.source:
        rows = [r for r in rows if r.get("source", "") == args.source]

    if not rows:
        print("No data available")
        return

    scored = [(score(r), r) for r in rows]
    scored.sort(key=lambda x: x[0])
    top10 = [r for _, r in scored[:10]]

    os.makedirs(os.path.dirname(args.output), exist_ok=True)
    fieldnames = list(top10[0].keys())
    with open(args.output, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(top10)

    print(f"Wrote {args.output}")


if __name__ == "__main__":
    main()
