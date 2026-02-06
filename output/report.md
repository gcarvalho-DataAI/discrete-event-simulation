# Discrete Event Simulation Challenge - Relatorio
## Modelo
- Filas: pedido, preparo, retirada.
- Tipos de clientes: fast/medium/slow com distribuicoes de chegada distintas.
- Tempo de preparo dependente do tamanho da fila (congestionamento).
- Abandono (reneging) quando a paciencia expira.
## Dados e instancias
Foram geradas 540 instancias (180 por dataset) a partir de Maven, Kaggle e Hugging Face, variando demanda, staffing, paciencia e fator de congestionamento.
## Resultados agregados por dataset
Valores medios nas instancias geradas.

| Dataset | N | Abandono medio | Espera pedido (min) | Espera preparo (min) | Espera retirada (min) | Tempo total (min) | Utilizacao preparo |
|---|---:|---:|---:|---:|---:|---:|---:|
| huggingface_coffeesales | 183 | 0.000 | 0.000 | 0.000 | 0.000 | 3.677 | 0.044 |
| kaggle_coffee_sales_dataset | 183 | 0.000 | 0.000 | 0.000 | 0.000 | 3.601 | 0.040 |
| maven_coffee_shop_sales | 183 | 0.394 | 3.227 | 28.055 | 0.002 | 42.788 | 0.974 |

## Conclusoes
- O dataset Maven gera um cenario de pico realista: abandono alto (media ~0.39) e preparo quase saturado (~97%).
- Kaggle e Hugging Face nao estressam o sistema (abandonos ~0 e utilizacao baixa), servindo como baseline.
- O gargalo dominante e a etapa de preparo; pedido e retirada quase nao impactam o tempo total nos cenarios de pico.

## Top-10 cenarios (melhor score)
Score combina abandono e tempos de espera (quanto menor, melhor).

| Rank | Instancia | Dataset | Abandono | Tempo total (min) | Espera preparo (min) | Utilizacao preparo |
|---:|---|---|---:|---:|---:|---:|
| 1 | kaggle_coffee_sales_dataset_037.json | kaggle_coffee_sales_dataset | 0.000 | 3.229 | 0.000 | 0.035 |
| 2 | kaggle_coffee_sales_dataset_038.json | kaggle_coffee_sales_dataset | 0.000 | 3.229 | 0.000 | 0.035 |
| 3 | kaggle_coffee_sales_dataset_039.json | kaggle_coffee_sales_dataset | 0.000 | 3.229 | 0.000 | 0.035 |
| 4 | kaggle_coffee_sales_dataset_040.json | kaggle_coffee_sales_dataset | 0.000 | 3.229 | 0.000 | 0.035 |
| 5 | kaggle_coffee_sales_dataset_041.json | kaggle_coffee_sales_dataset | 0.000 | 3.229 | 0.000 | 0.035 |
| 6 | kaggle_coffee_sales_dataset_042.json | kaggle_coffee_sales_dataset | 0.000 | 3.229 | 0.000 | 0.035 |
| 7 | kaggle_coffee_sales_dataset_043.json | kaggle_coffee_sales_dataset | 0.000 | 3.229 | 0.000 | 0.035 |
| 8 | kaggle_coffee_sales_dataset_044.json | kaggle_coffee_sales_dataset | 0.000 | 3.229 | 0.000 | 0.035 |
| 9 | kaggle_coffee_sales_dataset_045.json | kaggle_coffee_sales_dataset | 0.000 | 3.229 | 0.000 | 0.035 |
| 10 | kaggle_coffee_sales_dataset_046.json | kaggle_coffee_sales_dataset | 0.000 | 3.229 | 0.000 | 0.035 |

## Melhorias sugeridas
- Aumentar capacidade de baristas (preparo) e/ou reduzir variabilidade do tempo de preparo.
- Em cenarios Maven: testar +1 barista vs. reduzir paciencia (abandono) para entender trade-offs de fila.
- Se usar Kaggle/HF para pico, escalar taxas de chegada (ex.: 3x a 5x).
