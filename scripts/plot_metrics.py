#!/usr/bin/env python3
import argparse
import csv
import os
from typing import Dict, List

import matplotlib.pyplot as plt
import numpy as np


def read_csv(path: str) -> List[Dict[str, str]]:
    with open(path, newline="", encoding="utf-8") as f:
        return list(csv.DictReader(f))


def plot_box(ax, data, labels, title, ylabel, log=False):
    ax.boxplot(data, tick_labels=labels, showfliers=False)
    ax.set_title(title)
    ax.set_ylabel(ylabel)
    ax.tick_params(axis="x", rotation=20)
    if log:
        ax.set_yscale("symlog", linthresh=1e-3)


def plot_summary(ax, labels, means, p95s, title, ylabel):
    x = np.arange(len(labels))
    width = 0.35
    ax.bar(x - width / 2, means, width, label="Mean")
    ax.bar(x + width / 2, p95s, width, label="P95")
    ax.set_title(title)
    ax.set_ylabel(ylabel)
    ax.set_xticks(x)
    ax.set_xticklabels(labels, rotation=20)
    ax.legend()


def filter_rows(rows: List[Dict[str, str]], source: str) -> List[Dict[str, str]]:
    if not source:
        return rows
    return [r for r in rows if r.get("source", "") == source]


def make_plots(rows: List[Dict[str, str]], output_dir: str, prefix: str) -> None:
    if not rows:
        return

    abandon = np.array([float(row["abandonment_rate"]) for row in rows])
    order_wait = np.array([float(row["avg_order_wait"]) for row in rows])
    prep_wait = np.array([float(row["avg_prep_wait"]) for row in rows])
    pickup_wait = np.array([float(row["avg_pickup_wait"]) for row in rows])
    util_prep = np.array([float(row["prep_utilization"]) for row in rows])

    # Distributions
    fig, axes = plt.subplots(2, 1, figsize=(8, 8))
    plot_box(
        axes[0],
        [abandon, order_wait, prep_wait, pickup_wait],
        ["Abandon", "Order Wait", "Prep Wait", "Pickup Wait"],
        "Distribution of Waits and Abandonment",
        "Value",
        log=True,
    )
    plot_box(
        axes[1],
        [util_prep],
        ["Prep Util"],
        "Preparation Utilization (Distribution)",
        "Utilization",
    )
    fig.tight_layout()
    fig.savefig(os.path.join(output_dir, f"{prefix}_distributions.png"))
    plt.close(fig)

    # Summary (mean vs p95)
    labels = ["Abandon", "Order Wait", "Prep Wait", "Pickup Wait"]
    means = [np.mean(abandon), np.mean(order_wait), np.mean(prep_wait), np.mean(pickup_wait)]
    p95s = [np.percentile(abandon, 95), np.percentile(order_wait, 95), np.percentile(prep_wait, 95), np.percentile(pickup_wait, 95)]
    fig, ax = plt.subplots(figsize=(8, 4))
    plot_summary(ax, labels, means, p95s, "Summary (Mean vs P95)", "Value")
    fig.tight_layout()
    fig.savefig(os.path.join(output_dir, f"{prefix}_summary.png"))
    plt.close(fig)

    # Utilization summary
    fig, ax = plt.subplots(figsize=(6, 4))
    plot_summary(
        ax,
        ["Prep Util"],
        [np.mean(util_prep)],
        [np.percentile(util_prep, 95)],
        "Preparation Utilization (Mean vs P95)",
        "Utilization",
    )
    fig.tight_layout()
    fig.savefig(os.path.join(output_dir, f"{prefix}_util_summary.png"))
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

    sources = sorted({row.get("source", "") for row in rows})
    for src in sources:
        filtered = filter_rows(rows, src)
        if src:
            make_plots(filtered, args.output_dir, src)
    print(f"Plots written to {args.output_dir} (per dataset)")


if __name__ == "__main__":
    main()
