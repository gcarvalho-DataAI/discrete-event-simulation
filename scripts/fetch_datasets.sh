#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RAW_DIR="$ROOT_DIR/data/raw"
mkdir -p "$RAW_DIR"

# 1) Maven Analytics - Coffee Shop Sales (Maven Roasters)
MAVEN_URL="https://maven-datasets.s3.amazonaws.com/Coffee+Shop+Sales/Coffee+Shop+Sales.zip"
MAVEN_ZIP="$RAW_DIR/maven_coffee_shop_sales.zip"
MAVEN_OUT="$RAW_DIR/maven_coffee_shop_sales"
if [ ! -f "$MAVEN_ZIP" ]; then
  echo "Downloading Maven Analytics dataset..."
  curl -L -o "$MAVEN_ZIP" "$MAVEN_URL"
fi
mkdir -p "$MAVEN_OUT"
unzip -o "$MAVEN_ZIP" -d "$MAVEN_OUT" >/dev/null

# 2) Hugging Face - CoffeeSales (vending machine)
HF_URL="https://huggingface.co/datasets/tablegpt/CoffeeSales/resolve/main/index.csv"
HF_OUT="$RAW_DIR/hf_coffee_sales_index.csv"
if [ ! -f "$HF_OUT" ]; then
  echo "Downloading Hugging Face CoffeeSales dataset..."
  curl -L -o "$HF_OUT" "$HF_URL"
fi

# 3) Kaggle - saadaliyaseen/coffee-sales-dataset (Baselight mirror)
# Requires Kaggle API credentials: https://www.kaggle.com/docs/api
KAGGLE_DIR="$RAW_DIR/kaggle_coffee_sales_dataset"
if command -v kaggle >/dev/null 2>&1; then
  if [ -f "$HOME/.kaggle/kaggle.json" ]; then
    echo "Downloading Kaggle dataset saadaliyaseen/coffee-sales-dataset..."
    mkdir -p "$KAGGLE_DIR"
    kaggle datasets download -d saadaliyaseen/coffee-sales-dataset -p "$KAGGLE_DIR" --unzip
  else
    echo "Kaggle API credentials not found at ~/.kaggle/kaggle.json"
    echo "See https://www.kaggle.com/docs/api for setup."
  fi
else
  echo "Kaggle CLI not installed. Install with: uv pip install kaggle"
  echo "Manual download URL:"
  echo "  https://www.kaggle.com/datasets/saadaliyaseen/coffee-sales-dataset"
  echo "After download, move the .zip to $KAGGLE_DIR and unzip."
fi

echo "Done. Files are in $RAW_DIR"
