# API сайта и компоненты

Внутренние модули сайта теме недоступны: они меняются от версии к версии.
Всё, что нужно теме, даёт контракт:

- `useHost()` из `@sitebill/theme-sdk` — данные сайта и действия;
- компоненты из `@sitebill/theme-sdk/components` — готовые части сайта.

## useHost()

Вызывается в `setup` компонента темы.

```vue
<script setup lang="ts">
import { useHost } from '@sitebill/theme-sdk'
const host = useHost()
const { site, user, nav, links } = host
</script>

<template>
  <header>
    <span>{{ site.title }}</span>
    <a v-for="item in nav.main" :key="item.label" :href="item.to">{{ item.label }}</a>
    <a :href="links.login">{{ host.common('login') }}</a>
  </header>
</template>
```

| Поле | Что |
|---|---|
| `site` | `title`, `logoUrl`, `faviconUrl`, `contacts { email, phone, address, hours }`, `locale`, `locales`, `features { map, chat, aiSearch, booking, mortgageCalculator, registration }`, `edition` |
| `user` | `{ name, avatarUrl }` или `null`. **На сервере всегда `null`**: вход известен только в браузере, поэтому блоки пользователя оборачивайте в `SbClientOnly` |
| `nav.main` | главное меню (настраивается в админке) |
| `nav.user` | меню пользователя (кабинет, избранное, выход); для гостя пусто |
| `nav.footer` | меню подвала, если тенант его настроил; иначе пусто (стандартное меню рисует `SbFooterMenu`) |
| `nav.languages` | пункты переключения языка (`onSelect`) |
| `links` | `home`, `search`, `map`, `login`, `register`, `account`, `favorites`, `messages`, `addProperty` — уже с префиксом языка |
| `t(key, params?)` | текст темы из её `locales/<язык>.json`; параметры — `{name}` |
| `tc(key, count, params?)` | то же с формой по числу: формы через `|` в порядке правил языка (ru, sr: «{n} объект \| {n} объекта \| {n} объектов», en: «{n} item \| {n} items»); в тексте доступны `{n}` и `{count}` |
| `common(key)` | общие тексты сайта: `login`, `logout`, `register`, `favorites`, `messages`, `map`, `addProperty`, `newBuilding`, `notFound`, `similarProperties`, `leaveRequest`, `description`, `characteristics`, `address`, `documents`, `floorPlans` |
| `localePath(path)`, `navigate(to)` | путь с префиксом языка; переход |
| `format` | `price(value, currency)`, `area(value)`, `date(iso)`, `number(value)` |
| `data.use(name, vars?)` | данные сайта (см. ниже) |
| `head(input)` | мета-теги страницы (`title`, `meta`, `link`); SEO страницы объекта ставит сайт |
| `actions` | `openLogin()`, `logout()`, `setLocale(code)` |

### Данные: data.use

Произвольных запросов к API нет, есть именованные:

| Имя | Параметры | Результат |
|---|---|---|
| `freshProperties` | `{ limit?, dealType? }` | `PropertySummary[]` — свежие опубликованные |
| `citiesWithCount` | `{ limit? }` | `{ id, name, count, href }[]` |
| `siteStats` | — | `{ listings, cities }` |

Чтобы данные попали в HTML с сервера (это нужно для поисковиков и чтобы не было
мигания), дождитесь их в `<script setup>`:

```vue
<script setup lang="ts">
import { useHost } from '@sitebill/theme-sdk'
const host = useHost()
const stats = await host.data.use('siteStats')
</script>

<template>
  <p v-if="stats.data.value">{{ host.format.number(stats.data.value.listings) }}</p>
</template>
```

Без `await` данные придут уже в браузере. Нужен ещё запрос — напишите нам, мы
добавим его в контракт.

### Переводы темы

Тексты темы лежат в `locales/<язык>.json` (`ru`, `en`, `sr`, `tg`); языки
перечисляются в `manifest.json → locales`. Ключи вложенные:

```json
{ "hero": { "title": "Найдите своё жильё", "listings": "объявление | объявления | объявлений" } }
```

```vue
{{ host.t('hero.title') }}  {{ host.tc('hero.listings', 5) }}
```

Если перевода на текущий язык нет, берётся язык сайта по умолчанию, затем
`ru`, затем `en`. `sitebill-theme check` предупреждает о ключах без перевода.

## Компоненты сайта

```ts
import { SbLink, SbFavoriteButton, UButton } from '@sitebill/theme-sdk/components'
```

Настоящий компонент подставляет сайт, поэтому внешний вид галереи, карты и
формы поиска на сайте — как в стандартной теме. В песочнице вместо них
упрощённые заглушки.

| Компонент | Пропсы | Что делает |
|---|---|---|
| `SbLink` | `to`, `external?` | ссылка внутри сайта (без перезагрузки страницы) |
| `SbLogo` | `size?: 'sm' \| 'md' \| 'lg'` | логотип сайта |
| `SbSearchForm` | — | форма поиска (поля настраивает тенант) |
| `SbAiSearch` | — | ИИ-поиск (показывайте при `site.features.aiSearch`) |
| `SbPropertyGallery` | `images: MediaImage[]` | галерея с просмотром фото |
| `SbCharacteristics` | `property`, `hideTitle?` | полный блок характеристик, включая поля тенанта и их группы |
| `SbFavoriteButton` | `propertyId`, `isFavorited` | избранное (вход, счётчики — на стороне сайта) |
| `SbContactBlock` | `property`; событие `lead` | контакт, кнопка заявки, телефон, ссылка на источник |
| `SbMap` | `lat`, `lng`, `zoom?`, `title?` | карта |
| `SbMortgageCalculator` | `price`, `currency` | ипотечный калькулятор |
| `SbBookingWidget` | `property` | бронирование (при `property.booking?.enabled`) |
| `SbFloorPlans` | `images` | планировки с просмотром |
| `SbNotificationBell` | — | уведомления пользователя |
| `SbFooterMenu`, `SbFooterCounters`, `SbCopyright` | — | меню подвала, счётчики аналитики, копирайт |
| `SbClientOnly` | — | содержимое только в браузере |

Из Nuxt UI доступны `UButton`, `UBadge`, `UIcon`, `UContainer`, `UAvatar`,
`UDropdownMenu`, `UNavigationMenu`, `UColorModeButton`. Пропсы у них те же, что
в документации Nuxt UI v3; их стили уже есть на сайте и следуют цветам тенанта.

## Стили

`src/style.css` темы:

```css
@import "@sitebill/theme-sdk/tailwind.css";
@source "./";
```

- Tailwind собирается по файлам темы и попадает в `style.css` пакета. Сброса
  стилей (preflight) нет — он уже есть на сайте.
- Цвета и тёмный режим сайта — через классы Nuxt UI: `text-muted`,
  `text-highlighted`, `bg-default`, `bg-elevated`, `border-default`, `bg-primary`,
  `text-primary`, `primary-50…950`. Тёмный режим — вариант `dark:`.
- Стили только для своей темы ограничивайте атрибутом
  `[data-theme="custom:<id>"]` на `<html>`.
