#!/usr/bin/env python3
import argparse
import csv
import os
from collections import defaultdict
from typing import Dict, List

import matplotlib.pyplot as plt


def read_csv(path: str) -> List[Dict[str, str]]:
    with open(path, newline="", encoding="utf-8") as f:
        return list(csv.DictReader(f))


def plot_bar(ax, labels, values, title, ylabel):
    x = list(range(len(labels)))
    ax.bar(x, values)
    ax.set_title(title)
    ax.set_ylabel(ylabel)
    ax.set_xticks(x)
    ax.set_xticklabels(labels, rotation=30, ha="right")


def filter_rows(rows: List[Dict[str, str]], source: str) -> List[Dict[str, str]]:
    if not source:
        return rows
    return [r for r in rows if r.get("source", "") == source]


def make_plots(rows: List[Dict[str, str]], output_dir: str, prefix: str) -> None:
    if not rows:
        return

    labels = [row["instance"].replace(".json", "") for row in rows]
    abandon = [float(row["abandonment_rate"]) for row in rows]
    order_wait = [float(row["avg_order_wait"]) for row in rows]
    prep_wait = [float(row["avg_prep_wait"]) for row in rows]
    pickup_wait = [float(row["avg_pickup_wait"]) for row in rows]
    util_prep = [float(row["prep_utilization"]) for row in rows]

    fig, axes = plt.subplots(3, 1, figsize=(12, 12))
    plot_bar(axes[0], labels, abandon, "Abandonment Rate", "rate")
    plot_bar(axes[1], labels, order_wait, "Avg Order Wait (min)", "minutes")
    plot_bar(axes[2], labels, prep_wait, "Avg Prep Wait (min)", "minutes")
    fig.tight_layout()
    fig.savefig(os.path.join(output_dir, f"{prefix}_waits_abandonment.png"))
    plt.close(fig)

    fig, ax = plt.subplots(figsize=(12, 4))
    plot_bar(ax, labels, util_prep, "Prep Utilization", "utilization")
    fig.tight_layout()
    fig.savefig(os.path.join(output_dir, f"{prefix}_prep_utilization.png"))
    plt.close(fig)

    fig, ax = plt.subplots(figsize=(12, 4))
    plot_bar(ax, labels, pickup_wait, "Avg Pickup Wait (min)", "minutes")
    fig.tight_layout()
    fig.savefig(os.path.join(output_dir, f"{prefix}_pickup_wait.png"))
    plt.close(fig)


def main() -> None:
    parser = argparse.ArgumentParser(description="Plot simulation metrics")
    parser.add_argument("--input", default="output/metrics.csv")
    parser.add_argument("--output-dir", default="output")
    parser.add_argument("--source", default="", help="Filter by source (maven_coffee_shop_sales, huggingface_coffeesales, kaggle_coffee_sales_dataset)")
    args = parser.parse_args()

    rows = read_csv(args.input)
    if not rows:
        print("No data to plot")
        return

    os.makedirs(args.output_dir, exist_ok=True)

    if args.source:
        filtered = filter_rows(rows, args.source)
        make_plots(filtered, args.output_dir, args.source)
        print(f"Plots written to {args.output_dir} for source={args.source}")
        return

    # Default: one set per source
    sources = sorted({row.get("source", "") for row in rows})
    for src in sources:
        filtered = filter_rows(rows, src)
        if src:
            make_plots(filtered, args.output_dir, src)
    print(f"Plots written to {args.output_dir} (per dataset)")


if __name__ == "__main__":
    main()
