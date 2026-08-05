# Примеры кода

Каждый пример реализует один и тот же сценарий: загрузить документ,
дождаться индексации, задать по нему вопрос, удалить документ. Полное
описание методов — в [документации](../docs).

Все примеры принимают `BASE_URL` (адрес вашего сайта) и `API_KEY` через
переменные окружения.

| Язык | Файл | Зависимости |
|---|---|---|
| curl / bash | [curl/quickstart.sh](curl/quickstart.sh) | нет |
| JavaScript | [javascript/quickstart.js](javascript/quickstart.js) | Node.js 18+ (без npm-пакетов) |
| Python | [python/quickstart.py](python/quickstart.py) | `pip install requests` |
| PHP | [php/quickstart.php](php/quickstart.php) | PHP с расширением cURL (обычно уже включено) |

## Запуск

```bash
# curl
BASE_URL=https://ваш-сайт.ru API_KEY=<ключ> bash examples/curl/quickstart.sh

# JavaScript
BASE_URL=https://ваш-сайт.ru API_KEY=<ключ> node examples/javascript/quickstart.js

# Python
BASE_URL=https://ваш-сайт.ru API_KEY=<ключ> python examples/python/quickstart.py

# PHP
BASE_URL=https://ваш-сайт.ru API_KEY=<ключ> php examples/php/quickstart.php
```
