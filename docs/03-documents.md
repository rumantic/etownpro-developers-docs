# Документы

Все запросы требуют заголовок `Authorization: Bearer <ключ>`, см.
[«Аутентификация»](02-authentication.md).

## Разделы базы знаний

Каждый документ относится к одному из фиксированных разделов:

| `section` | Описание |
|---|---|
| `about` | О компании |
| `products` | Продукты |
| `services` | Услуги |
| `pricing` | Цены |
| `delivery` | Доставка |
| `payment` | Оплата |
| `faq` | FAQ |
| `reviews` | Отзывы |

---

## `GET /api/v1/documents`

Список документов.

**Query-параметры**

| Параметр | Обязателен | Описание |
|---|---|---|
| `section` | нет | фильтр по разделу (одно из значений выше) |
| `per_page` | нет | размер страницы, по умолчанию `20`, максимум `100` |

**Пример**

```bash
curl "https://ваш-сайт.ru/api/v1/documents?section=pricing&per_page=50" \
  -H "Authorization: Bearer <ключ>"
```

**Ответ `200`**

```json
{
  "data": [
    {
      "id": "42",
      "file_name": "price-list.pdf",
      "mime_type": "application/pdf",
      "size": 184320,
      "section": "pricing",
      "status": "indexed",
      "chunks_count": 37,
      "error_message": null,
      "created_at": "2026-08-04T10:11:00.000000Z",
      "indexed_at": "2026-08-04T10:11:42.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

---

## `GET /api/v1/documents/{id}`

Статус одного документа — тот же формат, что элемент списка выше. Так
опрашивается прогресс обработки после загрузки.

```bash
curl "https://ваш-сайт.ru/api/v1/documents/42" \
  -H "Authorization: Bearer <ключ>"
```

Если документа с таким `id` нет (или он принадлежит другому аккаунту) —
`404`.

---

## `POST /api/v1/documents`

Загрузить документ. `multipart/form-data`.

| Поле | Обязательно | Описание |
|---|---|---|
| `section` | да | один из разделов выше |
| `file` | да | сам файл, до **50 МБ** |

Поддерживаемые форматы: `pdf`, `doc`, `docx`, `xls`, `xlsx`, `csv`, `txt`,
`jpg`, `jpeg`, `png`, `webp`.

```bash
curl -X POST https://ваш-сайт.ru/api/v1/documents \
  -H "Authorization: Bearer <ключ>" \
  -F "section=pricing" \
  -F "file=@price-list.pdf"
```

**Ответ `201`**

```json
{
  "data": {
    "id": "42",
    "file_name": "price-list.pdf",
    "mime_type": "application/pdf",
    "size": 184320,
    "section": "pricing",
    "status": "pending",
    "chunks_count": null,
    "error_message": null,
    "created_at": "2026-08-04T10:11:00.000000Z",
    "indexed_at": null
  }
}
```

### Обработка асинхронная

Эндпоинт отвечает сразу же, не дожидаясь окончания обработки — документ
проходит через несколько промежуточных статусов, пока не станет
`indexed` (или `failed`, если что-то пошло не так). Опрашивайте
`GET /api/v1/documents/{id}` до финального статуса:

```
pending → downloading → parsing → chunking → embedding → saving → indexed
                                                                  ↘ failed
```

Для маленьких документов весь путь обычно занимает несколько секунд,
для больших — может доходить до пары минут. Пример цикла опроса — в
[примерах кода](../examples).

При `status: "failed"` причина — в поле `error_message` (например,
«файл повреждён» или «в документе не найдено текста»).

---

## `DELETE /api/v1/documents/{id}`

Удалить документ и его данные из базы знаний. Действие необратимо.

```bash
curl -X DELETE https://ваш-сайт.ru/api/v1/documents/42 \
  -H "Authorization: Bearer <ключ>"
```

**Ответ `200`**

```json
{ "data": { "id": "42", "deleted": true } }
```
