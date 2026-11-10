# Слоты и данные

Слот — часть сайта, которую тема может заменить. Тема отдаёт компоненты через
`defineTheme`, а список слотов перечисляет в `manifest.json → slots`. Оба
списка должны совпадать: `sitebill-theme check` это проверяет.

```ts
// src/index.ts
import { defineTheme } from '@sitebill/theme-sdk'
import './style.css'
import Header from './Header.vue'
import PropertyCard from './PropertyCard.vue'

export default defineTheme({ components: { Header, PropertyCard } })
```

## Слоты версии 1

| Слот | Где на сайте | Пропсы | События |
|---|---|---|---|
| `Header` | шапка всех страниц | — | — |
| `Footer` | подвал всех страниц | — | — |
| `Hero` | первый экран главной | — | — |
| `PropertyCard` | карточка в выдаче, подборках, похожих | `property: PropertySummary`, `viewMode: 'grid' \| 'list'` | `favorite-toggled` (boolean) — если рисуете своё избранное |
| `PropertyDetail` | страница объекта (без хлебных крошек и панели владельца) | `property: PropertyDetailData`, `similar: PropertySummary[]` | `open-lead-form` — показать форму заявки (её рисует сайт) |
| `HomeSections` | секции главной ниже первого экрана | `fresh: PropertySummary[]` | — |

Все слоты необязательны. Если тема какой-то не заменяет, на сайте остаётся
стандартный. Исключение — `HomeSections`: если он задан, стандартные секции
главной не рисуются.

Шапке, подвалу и первому экрану данные не передаются пропсами: меню, бренд,
контакты, языки и пользователя они берут из `useHost()` (см. [API сайта](host-api.md)).

Если на главной тенант собрал свои блоки в конструкторе страниц, первый экран и
секции главной рисует конструктор, а не тема.

## Типы в компонентах

```vue
<script setup lang="ts">
import type { PropertyCardProps } from '@sitebill/theme-sdk'
const props = defineProps<PropertyCardProps>()
</script>
```

## PropertySummary — объект в списке

Все подписи уже переведены на язык сайта и отформатированы.

| Поле | Тип | Пример / смысл |
|---|---|---|
| `id` | string | |
| `href` | string | готовая ссылка на страницу объекта, уже с префиксом языка |
| `title` | string | заголовок объявления |
| `dealType` / `dealTypeLabel` | string | `sale` / «Продажа»; бывают и значения, заведённые тенантом |
| `type` / `typeLabel` | string | `apartment` / «Квартира» |
| `price` | number \| null | |
| `currency` | string | `RUB`, `EUR`… |
| `priceFormatted` | string | «180 000 €»; для посуточной аренды — с периодом |
| `areaTotal` | number \| null | м² |
| `areaLabel` | string | «2 комн., 68 м²», «Студия, 25 м²» |
| `rooms` | number \| null | `0` — студия |
| `floor`, `floorsTotal` | number \| null | |
| `buildingTypeLabel` | string | «Кирпичный» |
| `address` | string | город, район, улица, дом одной строкой |
| `city`, `district` | `{ id, name } \| null` | |
| `coverUrl` | string \| null | обложка |
| `imagesCount` | number | |
| `isNewBuilding`, `isFavorited`, `isPublished` | boolean | `isPublished: false` видит только владелец — такую карточку можно приглушить |
| `publishedAt` / `publishedLabel` | string \| null | ISO-дата / «Опубликовано 3 ноября 2026» |
| `badges` | string[] | короткие признаки для бейджей: «Лифт», «Парковка» |

## PropertyDetailData — страница объекта

Всё из `PropertySummary` и ещё:

| Поле | Тип | Смысл |
|---|---|---|
| `heading` | string | заголовок страницы (h1): из SEO-шаблонов сайта, если заданы, иначе `title` |
| `descriptionHtml` | string | описание; HTML уже очищен сайтом, можно выводить через `v-html` |
| `images`, `floorPlans` | `MediaImage[]` | `{ id, url, thumbUrl, webpUrl, alt, width?, height? }` |
| `documents` | `{ url, name, mimeType, size }[]` | |
| `characteristics` | `{ key, label, value, group? }[]` | базовые характеристики; полный блок с полями, которые завёл тенант, рисует `SbCharacteristics` |
| `pricePerAreaFormatted` | string | «1 985 €/м²» |
| `videoEmbedUrl`, `tourUrl` | string \| null | ссылка на видео уже приведена к виду для встраивания |
| `location` | `{ lat, lng } \| null` | null — координат нет или карта выключена на сайте |
| `agent` | `{ name, organization, avatarUrl, sourceUrl } \| null` | автор объявления или агент из фида |
| `contact` | `{ name, roleLabel, phone, avatarUrl, ctaLabel, isPersonal }` | кому звонить; без автора — контакты сайта |
| `booking` | `{ enabled } \| null` | онлайн-бронирование (виджет — `SbBookingWidget`) |
| `viewsCount`, `updatedAt` | | |
| `viewerCanManage`, `editHref` | | показать «Редактировать» владельцу |

## Сетка выдачи

Классы контейнера карточек задаются в манифесте (`listing`), а не в коде:

```json
"listing": {
  "showViewToggle": true,
  "gridClass": { "grid": "grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4", "list": "flex flex-col gap-4" }
}
```

Эти классы стоят на элементе сайта, поэтому их нужно использовать и в коде темы
(как в стартовом шаблоне). Иначе Tailwind темы их не соберёт: стили сайта
содержат только классы, которые используются в самом сайте.
