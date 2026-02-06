# Discrete Event Simulation Challenge

Estrutura de projeto para simulação de eventos discretos (SimPy) de uma cafeteria
com filas de pedido, preparo e retirada.

LinkedIn: `https://www.linkedin.com/in/gabriel-carvalho-conceicao/`  
Repositório: `https://github.com/gcarvalho-DataAI/discrete-event-simulation`

## Estrutura

- `src/` código da simulação
- `data/` dados de entrada (opcional)
- `output/` resultados e gráficos
- `scripts/` utilitários

## Rodar com uv

```bash
uv venv
uv pip install -r requirements.txt
uv run python src/cafe_sim.py
```

Ou usando o entrypoint:

```bash
uv run cafe-sim
```

## Datasets

Baixar datasets para calibrar taxas de chegada e mix de produtos:

```bash
./scripts/fetch_datasets.sh
```

Detalhes e fontes em `data/README.md`.

## Paths

Todos os caminhos no projeto e nos relatórios são referenciados a partir da raiz do repositório (ex.: `data/instances/...`, `output/...`), sem prefixos do diretório do usuário.

## Gerar instâncias

Gera instâncias a partir dos 3 datasets:

```bash
uv run python scripts/build_instances.py
```

As instâncias são salvas em `data/instances/`.

## Rodar em lote + CSV

```bash
uv run python scripts/run_batch.py --instances-dir data/instances --output output/metrics.csv
```

## Gerar gráficos

```bash
uv run python scripts/plot_metrics.py --input output/metrics.csv --output-dir output
```

Por dataset:

```bash
uv run python scripts/plot_metrics.py --input output/metrics.csv --output-dir output --source maven_coffee_shop_sales
```

## Top-10 cenários

```bash
uv run python scripts/report_top10.py --input output/metrics.csv --output output/top10.csv
```

## Rodar com instância

```bash
uv run python src/cafe_sim.py --instance data/instances/huggingface_coffeesales_1.json
```

## Parâmetros

Veja os argumentos em `src/cafe_sim.py` para ajustar taxas de chegada, tempos de
serviço, paciência e capacidade de atendentes/baristas.
