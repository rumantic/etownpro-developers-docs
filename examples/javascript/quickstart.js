// Knowledge Base API — полный цикл: загрузить документ, дождаться
// индексации, задать вопрос, удалить документ.
//
// Требует Node.js 18+ (встроенные fetch/FormData/Blob, без зависимостей).
//
// Использование:
//   BASE_URL=https://ваш-сайт.ru API_KEY=<ключ> node quickstart.js

const BASE_URL = process.env.BASE_URL
const API_KEY = process.env.API_KEY

if (!BASE_URL || !API_KEY) {
  console.error('Укажите переменные окружения BASE_URL и API_KEY')
  process.exit(1)
}

const headers = { Authorization: `Bearer ${API_KEY}` }

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms))
}

async function main() {
  console.log('1) Загружаем тестовый документ...')
  const fileContent = 'Доставка по Москве — 500 рублей, бесплатно от 5000 рублей.'
  const form = new FormData()
  form.append('section', 'delivery')
  form.append('file', new Blob([fileContent], { type: 'text/plain' }), 'quickstart-doc.txt')

  let res = await fetch(`${BASE_URL}/api/v1/documents`, { method: 'POST', headers, body: form })
  let body = await res.json()
  console.log(body)
  if (!res.ok) throw new Error(`Загрузка не удалась: ${res.status}`)
  const docId = body.data.id
  console.log(`Документ создан, id=${docId}`)

  console.log('2) Ждём индексации...')
  let status = 'pending'
  for (let i = 0; i < 20; i++) {
    await sleep(2000)
    res = await fetch(`${BASE_URL}/api/v1/documents/${docId}`, { headers })
    body = await res.json()
    status = body.data.status
    console.log(`  статус: ${status}`)
    if (status === 'indexed' || status === 'failed') break
  }
  if (status !== 'indexed') throw new Error(`Индексация не завершилась успехом: ${JSON.stringify(body)}`)

  console.log('3) Задаём вопрос...')
  res = await fetch(`${BASE_URL}/api/v1/chat/ask`, {
    method: 'POST',
    headers: { ...headers, 'Content-Type': 'application/json' },
    body: JSON.stringify({ question: 'Сколько стоит доставка по Москве?' }),
  })
  body = await res.json()
  console.log(body.data.answer)
  console.log('источники:', body.data.sources)

  console.log('4) Удаляем тестовый документ...')
  res = await fetch(`${BASE_URL}/api/v1/documents/${docId}`, { method: 'DELETE', headers })
  console.log(await res.json())
}

main().catch(err => {
  console.error(err)
  process.exit(1)
})
