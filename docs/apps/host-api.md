# Host API

Всё, что приложению нужно от платформы. Классы ядра (`App\…`) не используйте: они не входят в контракт.

## PHP (`Sitebill\AppSdk\Host`, контракт 1.1)

| Вызов | Что делает |
|---|---|
| `Host::site()->current()` | `SiteInfo`: id сайта, название, url, язык, языки (`null` вне контекста сайта) |
| `Host::site()->runAs($tenantId, fn)` | выполнить код от имени сайта — для консоли и очередей |
| `Host::settings()->get/set/forget/all($appId, …)` | настройки приложения текущего сайта |
| `Host::settings()->secret()/setSecret()` | ключи API и пароли: шифруются в БД |
| `Host::crm()->findOrCreateContact()`, `findContact($id)` | контакты CRM (дедупликация по телефону и email) |
| `Host::crm()->createLead([...])` | контакт (дедупликация по телефону) и сделка в воронке по умолчанию; менеджеры получают уведомление как от встроенной формы |
| `Host::properties()` | чтение объявлений: `find($id)`, `latest($limit)`; адрес карточки — канонический |
| `Host::notifier()->notifyAdmins($appId, $title, $body, $url)` | уведомление менеджерам: колокольчик, почта, Telegram, Max (по настройкам сайта) |

Вне HTTP-запроса без `runAs` вызовы, требующие сайта, бросают исключение.

```php
$lead = Host::crm()->createLead([
    'name' => $name, 'phone' => $phone, 'message' => $comment,
    'property_id' => $propertyId, 'source' => 'acme.callback',
]);
```

## Браузер: объект `host`

Страницы и виджеты получают пропс `host`:

| Поле | Что |
|---|---|
| `host.t(key)` | перевод из `locales/` приложения |
| `host.locale` | язык сайта |
| `host.localePath('/admin/deals')` | путь с префиксом языка |
| `host.gql(query, variables)` | GraphQL сайта: свои поля приложения и опубликованные поля ядра |
| `host.rest(path, { method, body })` | `/api/v1/apps/<id>/<path>` с сессией и CSRF |
| `host.toast(text, 'success'\|'error')` | всплывающее сообщение |
| `host.formatDate(iso)` | дата и время в языке сайта |
| `host.user` | `{ id, name }` (публичный uuid) или `null` для гостя |
| `host.components` | `UButton`, `UInput`, `UTextarea`, `UBadge`, `UIcon`, `UModal`, `UCard`, `USwitch`, `USelect` |

Vue общий с сайтом — не включайте свою копию:

```js
const { defineComponent, h, ref } = globalThis.__SB_HOST__.vue
```

Голые импорты (`import … from 'пакет'`) в браузере не разрешатся: собирайте бандл в один файл или пишите на чистом ESM.
Типы — в `@sitebill/app-sdk` (`AppHost`, `AppPageProps`, `AppWidgetProps`, `defineAdminPages`, `defineWidgets`).
