<?php

/**
 * Knowledge Base API — полный цикл: загрузить документ, дождаться
 * индексации, задать вопрос, удалить документ.
 *
 * Без зависимостей (чистый cURL).
 *
 * Использование:
 *   BASE_URL=https://ваш-сайт.ru API_KEY=<ключ> php quickstart.php
 */

$baseUrl = getenv('BASE_URL');
$apiKey = getenv('API_KEY');

if (!$baseUrl || !$apiKey) {
    fwrite(STDERR, "Укажите переменные окружения BASE_URL и API_KEY\n");
    exit(1);
}

function request(string $method, string $url, array $headers, $body = null, bool $isMultipart = false): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, json_decode($response, true)];
}

$authHeader = "Authorization: Bearer $apiKey";

echo "1) Загружаем тестовый документ...\n";
$tmpFile = tempnam(sys_get_temp_dir(), 'quickstart-doc');
file_put_contents($tmpFile, 'Доставка по Москве — 500 рублей, бесплатно от 5000 рублей.');
rename($tmpFile, $tmpFile . '.txt');
$tmpFile .= '.txt';

[$status, $body] = request('POST', "$baseUrl/api/v1/documents", [$authHeader], [
    'section' => 'delivery',
    'file' => new CURLFile($tmpFile, 'text/plain', 'quickstart-doc.txt'),
]);
unlink($tmpFile);

if ($status !== 201) {
    fwrite(STDERR, "Загрузка не удалась ($status): " . json_encode($body, JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}
print_r($body);
$docId = $body['data']['id'];
echo "Документ создан, id=$docId\n";

echo "2) Ждём индексации...\n";
$docStatus = 'pending';
for ($i = 0; $i < 20; $i++) {
    sleep(2);
    [, $body] = request('GET', "$baseUrl/api/v1/documents/$docId", [$authHeader]);
    $docStatus = $body['data']['status'];
    echo "  статус: $docStatus\n";
    if (in_array($docStatus, ['indexed', 'failed'], true)) {
        break;
    }
}

if ($docStatus !== 'indexed') {
    fwrite(STDERR, "Индексация не завершилась успехом: " . json_encode($body, JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}

echo "3) Задаём вопрос...\n";
[, $body] = request(
    'POST',
    "$baseUrl/api/v1/chat/ask",
    [$authHeader, 'Content-Type: application/json'],
    json_encode(['question' => 'Сколько стоит доставка по Москве?'], JSON_UNESCAPED_UNICODE)
);
echo $body['data']['answer'] . "\n";
echo 'источники: ' . json_encode($body['data']['sources'], JSON_UNESCAPED_UNICODE) . "\n";

echo "4) Удаляем тестовый документ...\n";
[, $body] = request('DELETE', "$baseUrl/api/v1/documents/$docId", [$authHeader]);
print_r($body);
