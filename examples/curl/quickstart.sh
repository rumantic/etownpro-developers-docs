#!/bin/bash
# Knowledge Base API — полный цикл: загрузить документ, дождаться
# индексации, задать вопрос, удалить документ.
#
# Использование:
#   BASE_URL=https://ваш-сайт.ru API_KEY=<ключ> ./quickstart.sh
set -euo pipefail

BASE_URL="${BASE_URL:?Укажите BASE_URL, например https://ваш-сайт.ru}"
API_KEY="${API_KEY:?Укажите API_KEY}"
AUTH_HEADER="Authorization: Bearer $API_KEY"

echo "1) Загружаем тестовый документ..."
echo "Доставка по Москве — 500 рублей, бесплатно от 5000 рублей." > /tmp/quickstart-doc.txt

UPLOAD_RESPONSE=$(curl -sS -X POST "$BASE_URL/api/v1/documents" \
  -H "$AUTH_HEADER" \
  -F "section=delivery" \
  -F "file=@/tmp/quickstart-doc.txt")
echo "$UPLOAD_RESPONSE"

DOC_ID=$(echo "$UPLOAD_RESPONSE" | grep -o '"id":"[^"]*"' | head -1 | sed -E 's/.*:"(.*)"$/\1/')
echo "Документ создан, id=$DOC_ID"

echo "2) Ждём индексации..."
for i in $(seq 1 20); do
  sleep 2
  STATUS_RESPONSE=$(curl -sS "$BASE_URL/api/v1/documents/$DOC_ID" -H "$AUTH_HEADER")
  STATUS=$(echo "$STATUS_RESPONSE" | grep -o '"status":"[^"]*"' | head -1 | sed -E 's/.*:"(.*)"$/\1/')
  echo "  статус: $STATUS"
  if [ "$STATUS" = "indexed" ] || [ "$STATUS" = "failed" ]; then
    break
  fi
done

if [ "$STATUS" != "indexed" ]; then
  echo "Индексация не завершилась успехом: $STATUS_RESPONSE"
  exit 1
fi

echo "3) Задаём вопрос..."
# Кириллицу в теле надёжнее слать через файл (--data-binary @file), а не
# как аргумент -d "..." — на некоторых системах (например, git-bash на
# Windows, где curl — отдельный процесс) не-ASCII символы в аргументах
# командной строки могут побиться при передаче дочернему процессу.
cat > /tmp/quickstart-ask.json <<'EOF'
{"question": "Сколько стоит доставка по Москве?"}
EOF
curl -sS -X POST "$BASE_URL/api/v1/chat/ask" \
  -H "$AUTH_HEADER" \
  -H "Content-Type: application/json" \
  --data-binary @/tmp/quickstart-ask.json
echo

echo "4) Удаляем тестовый документ..."
curl -sS -X DELETE "$BASE_URL/api/v1/documents/$DOC_ID" -H "$AUTH_HEADER"
echo
