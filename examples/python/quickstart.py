"""
Knowledge Base API — полный цикл: загрузить документ, дождаться
индексации, задать вопрос, удалить документ.

Требует: pip install requests

Использование:
  BASE_URL=https://ваш-сайт.ru API_KEY=<ключ> python quickstart.py
"""

import os
import sys
import time

import requests

# На Windows консоль по умолчанию не всегда UTF-8 — без этого кириллица в
# print() может превратиться в мохибейк, хотя сами данные API корректны.
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")

BASE_URL = os.environ.get("BASE_URL")
API_KEY = os.environ.get("API_KEY")

if not BASE_URL or not API_KEY:
    sys.exit("Укажите переменные окружения BASE_URL и API_KEY")

headers = {"Authorization": f"Bearer {API_KEY}"}


def main():
    print("1) Загружаем тестовый документ...")
    file_content = "Доставка по Москве — 500 рублей, бесплатно от 5000 рублей.".encode("utf-8")
    resp = requests.post(
        f"{BASE_URL}/api/v1/documents",
        headers=headers,
        data={"section": "delivery"},
        files={"file": ("quickstart-doc.txt", file_content, "text/plain")},
    )
    resp.raise_for_status()
    doc = resp.json()["data"]
    print(doc)
    doc_id = doc["id"]
    print(f"Документ создан, id={doc_id}")

    print("2) Ждём индексации...")
    status = "pending"
    for _ in range(20):
        time.sleep(2)
        resp = requests.get(f"{BASE_URL}/api/v1/documents/{doc_id}", headers=headers)
        resp.raise_for_status()
        doc = resp.json()["data"]
        status = doc["status"]
        print(f"  статус: {status}")
        if status in ("indexed", "failed"):
            break

    if status != "indexed":
        sys.exit(f"Индексация не завершилась успехом: {doc}")

    print("3) Задаём вопрос...")
    resp = requests.post(
        f"{BASE_URL}/api/v1/chat/ask",
        headers=headers,
        json={"question": "Сколько стоит доставка по Москве?"},
    )
    resp.raise_for_status()
    answer = resp.json()["data"]
    print(answer["answer"])
    print("источники:", answer["sources"])

    print("4) Удаляем тестовый документ...")
    resp = requests.delete(f"{BASE_URL}/api/v1/documents/{doc_id}", headers=headers)
    resp.raise_for_status()
    print(resp.json())


if __name__ == "__main__":
    main()
