# CLI и публикация

CLI `sitebill-app` входит в npm-пакет `@sitebill/app-sdk` (Node 22.2+, зависимостей нет). Запускайте из корня проекта
приложения, где лежит `manifest.json`.

```bash
npx --package @sitebill/app-sdk sitebill-app check            # проверка
npx --package @sitebill/app-sdk sitebill-app pack             # проверка + <id>-<версия>.sbapp
npx --package @sitebill/app-sdk sitebill-app dev --compose ../community/docker-compose.yml
```

## `check`

Те же правила, что у установщика платформы, и несколько дополнительных предупреждений.

| Проверка | Результат |
|---|---|
| `manifest.json`: `id`, `version`, `name`, `sdk`, `provider`, `schedule` | ошибка |
| имена файлов, число (3000) и размер (64 МБ) | ошибка |
| `php -l` каждого `.php` (нужен `php`, иначе `--php <путь>`) | ошибка |
| пространство имён в `api/src` совпадает с `provider` | ошибка |
| миграции работают только с `app_<id>_*` | ошибка |
| `locales/{ru,en,sr,tg}.json`: все файлы, одинаковые ключи | ошибка |
| бандлы `web.admin`/`web.site` существуют | ошибка |
| мутации GraphQL без `@guard`, с голым `@guard` (без `@can`) | предупреждение |
| `use App\…` | предупреждение |
| виджет из манифеста не найден в бандле, голые импорты в бандле | предупреждение |
| нет `requires.host` | предупреждение |

## `pack`

`check`, затем архив `<id>-<версия>.sbapp` (zip) в текущем каталоге (`--out каталог`). В пакет входят файлы проекта
без служебных (`.`-файлы, `node_modules`, корневые `vendor`, `dist`, `tests`, `composer.lock`).

## `dev`

Копирует состав пакета в контейнер `api` стека Community и ставит его командой `apps install --force` — так же,
как при загрузке из админки. Дальше следит за файлами и переустанавливает при каждой правке (`--once` — один раз;
`--service api`, `--compose <файл>`). Стек должен работать с `APPS_ALLOW_UNSIGNED=true`.

## Установка

| Способ | Как |
|---|---|
| Админка | **Приложения → Установленные → Загрузить свой пакет** (только при `APPS_ALLOW_UNSIGNED=true`) |
| Консоль | `docker compose exec api php artisan apps install <каталог или zip> [--force]` |
| Управление | `apps list`, `enable`, `disable`, `rollback <id>`, `remove <id> [--with-data]`, `safe-mode on\|off` |

Неподписанные пакеты (ваш код, не из маркетплейса) разрешены только переменной окружения, не галочкой в интерфейсе;
при установке администратор видит плашку «пакет не проверен».

## Версии и совместимость

- Новые методы и поля контракта — в рамках мажорной версии (`sdk.php`/`sdk.web: "1"`); удаление — новый мажор.
- Требуемая версия платформы — в `requires.host`. Если платформу обновили до несовместимой версии, приложение получает
  статус `incompatible` и не загружается, а остальные работают.
- Поднимайте `version` при каждом выпуске; предыдущая версия сохраняется для `apps rollback`.

## Тесты

Пакет тестируется как обычный Laravel-код (Pest). Эталон — `tests/` в [sitebill-app-callback](https://github.com/Sitebill/sitebill-app-callback)
после выхода базового `AppTestCase`; пока интеграционные проверки установки — через `apps install` в `setuptest`-стеке.
