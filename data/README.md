# Datasets (raw)

This folder stores raw datasets used to calibrate arrival rates and product mix.

## 1) Maven Analytics - Coffee Shop Sales (Maven Roasters)
- Source: https://mavenanalytics.io/data-playground/coffee-shop-sales
- Downloaded via: https://maven-datasets.s3.amazonaws.com/Coffee+Shop+Sales/Coffee+Shop+Sales.zip
- Files extracted to: `data/raw/maven_coffee_shop_sales/`

## 2) Hugging Face - CoffeeSales (vending machine)
- Source: https://huggingface.co/datasets/tablegpt/CoffeeSales
- Downloaded file: `data/raw/hf_coffee_sales_index.csv`

## 3) Kaggle - saadaliyaseen/coffee-sales-dataset (Baselight mirror)
- Source: https://baselight.app/u/kaggle/dataset/saadaliyaseen_coffee_sales_dataset
- Kaggle dataset slug: `saadaliyaseen/coffee-sales-dataset`
- Requires Kaggle API credentials to download.

## Fetch script

```bash
./scripts/fetch_datasets.sh
```
