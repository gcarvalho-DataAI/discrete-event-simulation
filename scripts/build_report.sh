#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

uv run python scripts/build_report.py

cd reports
pdflatex -interaction=nonstopmode cafe_sim_report.tex >/dev/null
pdflatex -interaction=nonstopmode cafe_sim_report.tex >/dev/null

echo "PDF generated at reports/cafe_sim_report.pdf"
