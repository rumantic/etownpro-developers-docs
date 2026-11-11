# Пакет и манифест

## Состав пакета

```
manifest.json                 # только данные
api/
  src/                        # PHP, пространство имён SitebillApps\<Vendor>\<Name>\
  database/migrations/        # только таблицы app_<id>_* (с tenant_id)
  graphql/schema.graphql      # extend type Query / Mutation
  routes/api.php              # REST /api/v1/apps/<id>/*
web/
  admin.mjs                   # страницы и виджеты админки (экспорт pages, widgets)
  site.mjs                    # виджеты публичного сайта (экспорт widgets)
locales/{ru,en,sr,tg}.json    # переводы приложения
icon.svg, CHANGELOG.md        # по желанию
```

Файлы с точки (`.gitignore`, `.env`), `node_modules` и корневой `vendor` в пакет не попадают. Имя файла — латиница,
цифры, `_`, `-`, `.`; не больше 3000 файлов и 64 МБ в распакованном виде.

## `manifest.json`

```json
{
  "id": "acme.callback",
  "name": { "ru": "Обратный звонок", "en": "Callback" },
  "version": "1.3.0",
  "vendor": { "name": "ACME", "url": "https://acme.example" },
  "sdk": { "php": "1", "web": "1" },
  "requires": { "host": ">=1.1" },
  "provider": "SitebillApps\\Acme\\Callback\\CallbackServiceProvider",
  "permissions": [],
  "admin": {
    "menu": [{ "label": { "ru": "Обратный звонок", "en": "Callback" }, "icon": "i-lucide-phone", "path": "" }],
    "pages": ["", "settings"],
    "widgets": { "admin.dashboard": "CallbackStats" }
  },
  "web": { "admin": "web/admin.mjs", "site": "web/site.mjs" },
  "site": { "widgets": { "property.detail": "CallbackForm", "global": "CallbackFloatingButton" } },
  "schedule": [{ "command": "acme-callback:cleanup", "cron": "30 3 * * *" }]
}
```

| Поле | Правило |
|---|---|
| `id` | `vendor.name`: латиница в нижнем регистре, цифры, дефис. Таблицы — `app_<id с . и - → _>_*` |
| `version` | semver `X.Y.Z`; поднимайте при каждом выпуске |
| `sdk` | `{"php": "1", "web": "1"}` — хотя бы одна часть |
| `requires.host` | версия контракта платформы для приложений (`>=1.1`, `^1.2`); у несовместимой установки приложение получает статус `incompatible` |
| `provider` | класс в `SitebillApps\<Vendor>\<Name>\…`, наследник `Sitebill\AppSdk\AppServiceProvider` |
| `admin.menu` | пункты меню админки; `path` — подпуть страницы (`""` — главная приложения) |
| `admin.pages` | пути страниц, которые экспортирует `web/admin.mjs` (`export const pages = { '': …, 'settings': … }`), адреса `/admin/x/<id>/<путь>` |
| `schedule` | `command` обязан начинаться с `<id с . → ->:` (`acme-callback:`), `cron` — 5 полей; ядро запускает с `withoutOverlapping` и `onOneServer` |

Манифест — только данные: меню, маршруты, расписание и зависимости видны до загрузки кода.

## База данных

```php
Schema::create('app_acme_callback_requests', function (Blueprint $t) {
    $t->id();
    $t->unsignedBigInteger('tenant_id')->index();
    // …
});
```

Модель — наследник `Sitebill\AppSdk\AppModel`: `tenant_id` заполняется и фильтруется автоматически. Установщик
отвергает миграции, трогающие чужие таблицы.

## GraphQL и REST

- `api/graphql/schema.graphql` дополняет схему платформы: `extend type Query`, `extend type Mutation`, свои типы с
  префиксом приложения. Резолверы — классы приложения (`@field(resolver: "SitebillApps\\Acme\\Callback\\GraphQL\\…")`).
- Админские мутации и запросы — `@guard @can(ability: "admin", model: "Tenant")`.
- REST из `routes/api.php` доступен по `/api/v1/apps/<id>/…` под сайтом запроса. Публичные точки (форма для гостя)
  защищайте сами: поле-ловушка, лимит запросов, валидация.

## Консоль и очереди

Без HTTP-запроса сайт не привязан, и `TenantScope` ничего не фильтрует. Оборачивайте работу по сайтам:

```php
Host::site()->runAs($tenantId, fn () => Request::query()->where('created_at', '<', $border)->delete());
```

## Переводы

`locales/{ru,en,sr,tg}.json` — плоские ключи. В браузере — `host.t('key')` (язык сайта, запасные `en`, затем `ru`).
`check` требует все четыре файла с одинаковым набором ключей.
