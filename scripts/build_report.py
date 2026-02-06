#!/usr/bin/env python3
import csv
import os
import platform
import subprocess
from datetime import datetime
from statistics import mean
from collections import defaultdict

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUTPUT_DIR = os.path.join(ROOT_DIR, "output")
REPORTS_DIR = os.path.join(ROOT_DIR, "reports")
METRICS_PATH = os.path.join(OUTPUT_DIR, "metrics.csv")
TOP10_PATH = os.path.join(OUTPUT_DIR, "top10.csv")


def f(row, k):
    return float(row.get(k, 0))


def read_csv(path):
    if not os.path.exists(path):
        return []
    with open(path, newline="", encoding="utf-8") as f:
        return list(csv.DictReader(f))


def build_report_tex():
    rows = read_csv(METRICS_PATH)

    def f(row, k):
        return float(row.get(k, 0))

    def tex_escape(text: str) -> str:
        return (
            text.replace("\\", "\\textbackslash{}")
            .replace("&", "\\&")
            .replace("%", "\\%")
            .replace("$", "\\$")
            .replace("#", "\\#")
            .replace("_", "\\_")
            .replace("{", "\\{")
            .replace("}", "\\}")
            .replace("~", "\\textasciitilde{}")
            .replace("^", "\\textasciicircum{}")
        )

    source_labels = {
        "maven_coffee_shop_sales": "Maven (Coffee Shop Sales)",
        "huggingface_coffeesales": "Hugging Face (CoffeeSales)",
        "kaggle_coffee_sales_dataset": "Kaggle (Coffee Sales Dataset)",
    }

    by_source = defaultdict(list)
    for r in rows:
        by_source[r["source"]].append(r)

    summary = {}
    for src, rs in by_source.items():
        summary[src] = {
            "n": len(rs),
            "avg_abandon": mean(f(r, "abandonment_rate") for r in rs),
            "avg_order_wait": mean(f(r, "avg_order_wait") for r in rs),
            "avg_prep_wait": mean(f(r, "avg_prep_wait") for r in rs),
            "avg_pickup_wait": mean(f(r, "avg_pickup_wait") for r in rs),
            "avg_system_time": mean(f(r, "avg_system_time") for r in rs),
            "avg_prep_util": mean(f(r, "prep_utilization") for r in rs),
        }

    def score(row):
        abandonment = f(row, "abandonment_rate")
        system_time = f(row, "avg_system_time")
        order_wait = f(row, "avg_order_wait")
        prep_wait = f(row, "avg_prep_wait")
        pickup_wait = f(row, "avg_pickup_wait")
        return abandonment * 100.0 + system_time * 2.0 + order_wait + prep_wait + pickup_wait * 0.5

    # Top-10 focused on Maven (peak-like demand)
    maven_rows = [r for r in rows if r.get("source") == "maven_coffee_shop_sales"]
    maven_rows = sorted(maven_rows, key=score)
    top10 = maven_rows[:10]

    os.makedirs(REPORTS_DIR, exist_ok=True)
    tex_path = os.path.join(REPORTS_DIR, "cafe_sim_report.tex")
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    python_version = platform.python_version()
    os_info = f"{platform.system()} {platform.release()}"
    machine_info = platform.machine()
    processor = platform.processor() or "Unknown"

    def cmd_output(cmd):
        try:
            return subprocess.check_output(cmd, text=True).strip()
        except Exception:
            return "Unavailable"

    uv_version = cmd_output(["uv", "--version"])
    cpu_model = cmd_output(["bash", "-lc", "lscpu | awk -F: '/Model name/ {print $2}' | sed 's/^ *//'"])
    mem_total = cmd_output(["bash", "-lc", "free -h | awk '/Mem:/ {print $2}'"])

    with open(tex_path, "w", encoding="utf-8") as f:
        f.write(f"""
\\documentclass[11pt,a4paper]{{article}}
\\usepackage[utf8]{{inputenc}}
\\usepackage{{amsmath,amssymb}}
\\usepackage{{graphicx}}
\\usepackage{{tabularx}}
\\usepackage{{geometry}}
\\geometry{{margin=2.5cm}}
\\graphicspath{{{{../output/}}}}

% Clickable links without hyperref (no extra dependencies)
\\def\\href#1#2{{\\pdfstartlink attr{{/Border[0 0 0]}} user{{/Subtype/Link/A<< /S/URI /URI(\\pdfescapestring{{#1}}) >>}}#2\\pdfendlink}}
\\def\\url#1{{\\href{{#1}}{{\\texttt{{#1}}}}}}

\\begin{{document}}
\\section*{{Discrete Event Simulation Challenge}}
\\textbf{{Cafe Operations: Bottleneck Identification and Service Improvements}}

\\textbf{{Author:}} Gabriel Carvalho Domingos da Conceição

\\textbf{{LinkedIn:}} \\url{{https://www.linkedin.com/in/gabriel-carvalho-conceicao/}}

\\textbf{{Repository:}} \\url{{https://github.com/gcarvalho-DataAI/discrete-event-simulation}}

\\textbf{{Generated:}} {timestamp}

\\hrulefill

\\tableofcontents
\\listoftables
\\listoffigures
\\newpage

\\section{{Abstract}}
This report presents a discrete-event simulation model for a small cafe with separate queues for ordering, preparation, and pickup. Customers are segmented into fast, medium, and slow types with distinct arrival rates, service times, and patience thresholds. The simulation captures peak behavior, identifies bottlenecks, and compares scenarios across multiple datasets. The primary objective is to locate the dominant bottleneck under peak conditions and propose operational improvements.

\\hrulefill

\\section{{Introduction}}
Service systems such as cafes experience short periods of high demand where small capacity mismatches generate large delays. Discrete-event simulation (DES) is well suited for this environment because state changes occur at discrete events: arrivals, service start, service end, and abandonment.

\\hrulefill

\\section{{Theoretical Background}}
The cafe can be modeled as a multi-stage queueing system with heterogeneous customers and reneging. Key foundations:
\\begin{{itemize}}
  \\item \\textbf{{Queueing theory:}} arrival processes are modeled as Poisson processes, leading to exponential inter-arrival times. Service times are modeled using distributions such as triangular or lognormal.
  \\item \\textbf{{Reneging (impatient customers):}} customers may abandon the queue after exceeding a patience threshold, a common extension to M/G/c systems.
  \\item \\textbf{{State-dependent service:}} preparation time can increase with queue length due to congestion and human workload effects.
  \\item \\textbf{{DES methodology:}} systems evolve via events, and performance is measured via throughput, utilization, and waiting times.
\\end{{itemize}}

Representative references are listed in the References section.

\\hrulefill

\\section{{Model Specification}}
\\subsection{{System Components}}
\\begin{{itemize}}
  \\item Queues: ordering, preparation, pickup.
  \\item Resources: order attendants, baristas (preparation), pickup attendant.
  \\item Customer types: fast, medium, slow.
\\end{{itemize}}

\\subsection{{Event Flow}}
\\begin{{itemize}}
  \\item Arrival $\\rightarrow$ order queue $\\rightarrow$ preparation queue $\\rightarrow$ pickup queue $\\rightarrow$ exit.
  \\item Reneging occurs if waiting time in the order queue exceeds customer patience.
  \\item Preparation time increases with queue length by factor $(1 + \\alpha \\cdot q)$.
\\end{{itemize}}

\\subsection{{Metrics}}
The simulation reports:
\\begin{{itemize}}
  \\item Abandonment rate.
  \\item Average waiting times per stage.
  \\item Total time in system.
  \\item Resource utilization.
  \\item Average queue lengths.
\\end{{itemize}}

\\hrulefill

\\section{{Simulation Setup}}
\\begin{{itemize}}
  \\item Horizon: 4 hours of operation with 0.5 hour warm-up (default).
  \\item Inter-arrival times: exponential by customer type.
  \\item Service times: triangular around the type mean.
  \\item Patience: exponential by customer type.
  \\item Congestion: prep time multiplied by $(1 + \\alpha \\cdot q)$.
  \\item Random seed: 42 (default).
\\end{{itemize}}

\\hrulefill

\\section{{Data and Instance Generation}}
Three public datasets were used to estimate arrival profiles and product mix:
\\begin{{itemize}}
  \\item Maven Analytics (Coffee Shop Sales): \\url{{https://mavenanalytics.io/data-playground/coffee-shop-sales}}
  \\item Hugging Face (CoffeeSales): \\url{{https://huggingface.co/datasets/tablegpt/CoffeeSales}}
  \\item Kaggle (Coffee Sales Dataset): \\url{{https://www.kaggle.com/datasets/saadaliyaseen/coffee-sales-dataset}}
\\end{{itemize}}

Story of instance collection and generation:
\\begin{{itemize}}
  \\item The Maven dataset was downloaded as an XLSX file and parsed to extract transaction timestamps and product names.
  \\item The Hugging Face dataset was obtained as a CSV (vending-machine transactions) and used to validate arrival patterns.
  \\item The Kaggle dataset was downloaded manually as a ZIP, then extracted to CSV with date and time columns.
  \\item Product names were mapped into fast/medium/slow categories using keyword rules.
  \\item Peak-hour rates and product mix were computed per dataset and then used to generate synthetic peak scenarios.
\\end{{itemize}}

From each dataset, peak-hour rates and product mix were derived to generate instances. For each dataset, 180 instances were created by varying:
\\begin{{itemize}}
  \\item Demand scale: $0.7, 0.85, 1.0, 1.15, 1.3$
  \\item Staffing combinations (order/barista/pickup)
  \\item Customer patience levels
  \\item Preparation congestion factor $\\alpha$
\\end{{itemize}}

Additional synthetic instances were generated by combining multiple staffing and patience levels with congestion sensitivity, in order to stress-test the system under different peak conditions.

\\hrulefill

\\section{{Environment and Reproducibility}}
\\noindent
\\renewcommand{{\\arraystretch}}{{1.2}}
\\setlength{{\\tabcolsep}}{{6pt}}
\\begin{{minipage}}{{\\textwidth}}
\\begin{{tabularx}}{{\\textwidth}}{{lX}}
\\hline
OS & {os_info} \\\\
Machine & {machine_info} \\\\
CPU & {cpu_model} \\\\
RAM & {mem_total} \\\\
Python & {python_version} \\\\
UV & {uv_version} \\\\
\\hline
\\end{{tabularx}}

\\vspace{{0.6em}}
\\noindent \\textbf{{Usage:}}
\\begin{{verbatim}}
uv venv
uv pip install -r requirements.txt
uv run python src/cafe_sim.py
uv run python scripts/build_report.py
pdflatex reports/cafe_sim_report.tex
\\end{{verbatim}}
\\end{{minipage}}

\hrulefill

\section{{Solver and Simulation Choices}}
This problem is a discrete-event simulation, not a mathematical optimization solved by a MILP/CP solver. The core engine is SimPy (process-based DES), which is appropriate for queueing systems with reneging and state-dependent service times.

\\begin{{itemize}}
  \\item Solver choice: not applicable (SimPy event scheduling).
  \\item Event calendar: SimPy event queue.
  \\item Randomness: exponential inter-arrival, triangular service times, exponential patience.
  \\item SimPy: \\url{{https://simpy.readthedocs.io/en/latest/}}
\\end{{itemize}}

\\hrulefill

\\hrulefill

\\section{{Aggregate Results}}
\\begin{{table}}[h]
\\centering
\\small
\\caption{{Aggregate metrics by dataset (mean across instances).}}
\\label{{tab:aggregate}}
\\begin{{tabular}}{{lrrrrrrr}}
\\hline
Dataset & N & Abandon & Order Wait & Prep Wait & Pickup Wait & Total Time & Prep Util\\\\
\\hline
""")

        for src, s in summary.items():
            label = source_labels.get(src, src)
            f.write(
                f"{tex_escape(label)} & {s['n']} & {s['avg_abandon']:.3f} & {s['avg_order_wait']:.3f} & {s['avg_prep_wait']:.3f} & {s['avg_pickup_wait']:.3f} & {s['avg_system_time']:.3f} & {s['avg_prep_util']:.3f}\\\\\n"
            )

        f.write(r"""\hline
\end{tabular}
\end{table}

\section{Bottleneck Analysis}
The preparation stage consistently dominates queueing time in high-demand scenarios. In the Maven dataset, preparation utilization approaches saturation and abandonment becomes significant. Kaggle and Hugging Face datasets remain below capacity and serve as baseline (low-stress) scenarios. This indicates that improvements should focus on preparation capacity and variability rather than on ordering or pickup.

\section{Plots by Dataset}
\subsection*{Maven}
\begin{figure}[h]
\centering
\includegraphics[width=0.95\textwidth]{maven_coffee_shop_sales_waits_abandonment.png}
\caption{Maven: waits and abandonment}
\end{figure}

\begin{figure}[h]
\centering
\includegraphics[width=0.95\textwidth]{maven_coffee_shop_sales_prep_utilization.png}
\caption{Maven: preparation utilization}
\end{figure}

\begin{figure}[h]
\centering
\includegraphics[width=0.95\textwidth]{maven_coffee_shop_sales_pickup_wait.png}
\caption{Maven: pickup wait}
\end{figure}

\subsection*{Hugging Face}
\begin{figure}[h]
\centering
\includegraphics[width=0.95\textwidth]{huggingface_coffeesales_waits_abandonment.png}
\caption{Hugging Face: waits and abandonment}
\end{figure}

\begin{figure}[h]
\centering
\includegraphics[width=0.95\textwidth]{huggingface_coffeesales_prep_utilization.png}
\caption{Hugging Face: preparation utilization}
\end{figure}

\begin{figure}[h]
\centering
\includegraphics[width=0.95\textwidth]{huggingface_coffeesales_pickup_wait.png}
\caption{Hugging Face: pickup wait}
\end{figure}

\subsection*{Kaggle}
\begin{figure}[h]
\centering
\includegraphics[width=0.95\textwidth]{kaggle_coffee_sales_dataset_waits_abandonment.png}
\caption{Kaggle: waits and abandonment}
\end{figure}

\begin{figure}[h]
\centering
\includegraphics[width=0.95\textwidth]{kaggle_coffee_sales_dataset_prep_utilization.png}
\caption{Kaggle: preparation utilization}
\end{figure}

\begin{figure}[h]
\centering
\includegraphics[width=0.95\textwidth]{kaggle_coffee_sales_dataset_pickup_wait.png}
\caption{Kaggle: pickup wait}
\end{figure}

\section{Top-10 Scenarios (Maven)}
Top-10 is computed only from Maven instances to reflect peak-like demand. The score is:
\[
Score = 100 \cdot Abandon + 2 \cdot T_{system} + W_{order} + W_{prep} + 0.5 \cdot W_{pickup}
\]
Lower is better.
\begin{table}[h]
\centering
\scriptsize
\caption{Top-10 scenarios (Maven only).}
\label{tab:top10}
\resizebox{\textwidth}{!}{
\begin{tabular}{llllrrr}
\hline
Rank & Instance & Dataset & Abandon & Total Time & Prep Wait & Prep Util\\
\hline
""")

        for i, r in enumerate(top10, start=1):
            label = source_labels.get(r["source"], r["source"])
            f.write(
                f"{i} & {tex_escape(r['instance'])} & {tex_escape(label)} & {float(r['abandonment_rate']):.3f} & {float(r['avg_system_time']):.3f} & {float(r['avg_prep_wait']):.3f} & {float(r['prep_utilization']):.3f}\\\\\n"
            )

        f.write(r"""\hline
\end{tabular}
}
\end{table}

\section{Recommendations}
\begin{itemize}
  \item Increase preparation capacity (additional barista) or reduce prep variability.
  \item For Maven-like peak conditions, focus on prep queue control rather than order or pickup.
  \item For Kaggle/HF datasets, scale arrival rates to simulate true peak demand.
\end{itemize}

\clearpage
\section{References}
\begin{itemize}
  \item Banks, J., Carson, J. S., Nelson, B. L., \& Nicol, D. M. (2010). \textit{Discrete-Event System Simulation}. Pearson.
  \item Law, A. M. (2015). \textit{Simulation Modeling and Analysis}. McGraw-Hill.
  \item Kleinrock, L. (1975). \textit{Queueing Systems, Volume 1: Theory}. Wiley.
  \item Baccelli, F., \& Hebuterne, G. (1981). On Queues with Impatient Customers.
  \item Zohar, E., Mandelbaum, A., \& Shimkin, N. (2002). Adaptive Behavior of Impatient Customers in Queues.
  \item George, J. M., \& Harrison, J. M. (2001). Dynamic Control of a Queue with Variable Service Rate.
  \item KC, D. S., \& Terwiesch, C. (2009). Impact of Workload on Service Time and Quality: An Analysis of Hospital Operations.
\end{itemize}
\end{document}
""")

    return tex_path


def main():
    tex_path = build_report_tex()
    print(f"Wrote {tex_path}")


if __name__ == "__main__":
    main()
