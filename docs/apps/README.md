# Устанавливаемые приложения (App SDK)

Руководство для разработчиков, которые делают **приложение** для платформы
(Community Edition): свои таблицы, GraphQL, REST, страницы админки и виджеты
сайта. Приложение — обычный пакет **Laravel (PHP 8.4) + Vue 3**. Администратор
ставит его из `.sbapp`/`.zip`, пересобирать образ Docker и сайт не нужно, а
обновление платформы приложение переживает (контракт внутри мажорной версии
только расширяется).

> **Превью 0.x.** Контракт стабилизируется, до 1.0 названия могут меняться.

## Где что лежит

| Что | Где |
|---|---|
| PHP SDK (Composer) | `sitebill/app-sdk` · исходники [Sitebill/sitebill-app-sdk](https://github.com/Sitebill/sitebill-app-sdk) |
| Web SDK и CLI (npm) | `@sitebill/app-sdk` (тот же репозиторий, каталог `js/`) |
| Стартовое приложение | [Sitebill/sitebill-app-starter](https://github.com/Sitebill/sitebill-app-starter) |
| Эталонное приложение «Обратный звонок → CRM» | [Sitebill/sitebill-app-callback](https://github.com/Sitebill/sitebill-app-callback) |
| Платформа | [sitebill.ru](https://www.sitebill.ru/) (RU) · [sitebillcms.com](https://sitebillcms.com/) (EN) |

## Документы

| Документ | О чём |
|---|---|
| [Пакет и манифест](package.md) | состав пакета, `manifest.json`, расписание, переводы |
| [Host API](host-api.md) | PHP: `Host::site()/settings()/crm()/properties()/notifier()`; браузер: объект `host` |
| [Слоты и виджеты](slots-and-widgets.md) | куда встраиваются страницы и виджеты |
| [CLI и публикация](cli.md) | `sitebill-app check`, `pack`, `dev`; установка; неподписанные пакеты |

## Быстрый старт

1. Возьмите [стартовое приложение](https://github.com/Sitebill/sitebill-app-starter) («Use this template»).
2. В `manifest.json` поменяйте `id` (`vendor.name`), `name`, `version`, класс `provider`
   (пространство имён PHP — `SitebillApps\<Vendor>\<Name>\`).
3. Таблицы — только с префиксом `app_<id>_` и колонкой `tenant_id`; модели наследуйте от `AppModel`.
4. Мутации GraphQL — с `@guard` и `@can`: права проверяет сервер, голый `@guard` пускает любого зарегистрированного посетителя.
5. Проверьте и соберите пакет: `npx --package @sitebill/app-sdk sitebill-app check` и `… pack`.
6. Загрузите `.sbapp` в админке: **Приложения → Установленные → Загрузить свой пакет** (нужно `APPS_ALLOW_UNSIGNED=true`).

## Как это работает

- PHP-часть лежит в томе `/data/apps/<id>/<версия>` и подключается в рантайме: провайдер регистрируется
  в контейнере Laravel, миграции и GraphQL-схема добавляются без пересборки.
- Ошибка в приложении помечает его как `broken` — сайт и другие приложения продолжают работать.
  Есть безопасный режим (`php artisan apps safe-mode on`), он отключает все приложения.
- Установка — транзакционная: распаковка → статические проверки → смоук-запуск в отдельном процессе →
  миграции → сброс кэшей. При сбое возвращается предыдущая версия (`apps rollback <id>`).
- Браузерная часть — обычные ESM-файлы (`web/admin.mjs`, `web/site.mjs`); Vue общий с сайтом.

## Принципы

- Всё, что нужно от платформы, — через `Sitebill\AppSdk\Host` и объект `host`; классы ядра (`App\…`) не используйте.
- Приложение не меняет таблицы и GraphQL-типы ядра: только свои таблицы и свои поля.
- Все строки интерфейса — в `locales/{ru,en,sr,tg}.json`, ключи во всех четырёх языках.
- Расписание — в манифесте, а не в коде: ядро регистрирует его само.
