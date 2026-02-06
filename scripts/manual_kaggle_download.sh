#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RAW_DIR="$ROOT_DIR/data/raw"
KAGGLE_DIR="$RAW_DIR/kaggle_coffee_sales_dataset"

mkdir -p "$KAGGLE_DIR"

KAGGLE_URL="https://www.kaggle.com/datasets/saadaliyaseen/coffee-sales-dataset"

open_browser() {
  if command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$KAGGLE_URL" >/dev/null 2>&1 || true
  elif command -v open >/dev/null 2>&1; then
    open "$KAGGLE_URL" >/dev/null 2>&1 || true
  else
    echo "Open this URL in your browser: $KAGGLE_URL"
  fi
}

find_latest_zip() {
  local dir="$1"
  ls -t "$dir"/*.zip 2>/dev/null | head -n 1 || true
}

echo "Opening Kaggle dataset page in your browser..."
open_browser

echo "Waiting for the Kaggle .zip download to appear in ~/Downloads..."

echo "Press Ctrl+C to cancel."

while true; do
  ZIP_FILE=$(find_latest_zip "$HOME/Downloads")
  if [ -n "$ZIP_FILE" ]; then
    echo "Found zip: $ZIP_FILE"
    echo "Moving to $KAGGLE_DIR..."
    mv "$ZIP_FILE" "$KAGGLE_DIR/"
    echo "Unzipping..."
    unzip -o "$KAGGLE_DIR/$(basename "$ZIP_FILE")" -d "$KAGGLE_DIR/" >/dev/null
    echo "Done. Files are in $KAGGLE_DIR"
    break
  fi
  sleep 2
 done
