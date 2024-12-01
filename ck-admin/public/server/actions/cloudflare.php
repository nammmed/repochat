<?php
function saveCloudflareCredentials($data)
{
    global $db;

    try {
        if (!isset($data['siteId']) || !isset($data['cf_email']) || !isset($data['cf_api_key'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $email = $data['cf_email'];
        $apiKey = $data['cf_api_key'];

        // Шифрование apiKey
        $encryptedApiKey = encryptData($apiKey, $_SESSION['securityKey']);

        // Сохранение в базе данных
        $query = $db->prepare("UPDATE sites SET cf_email = :email, cf_api_key = :api_key WHERE id = :id");
        $query->bindParam(':email', $email);
        $query->bindParam(':api_key', $encryptedApiKey);
        $query->bindParam(':id', $siteId);
        $query->execute();

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        error_log('Ошибка в saveCloudflareCredentials: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getCloudflareCredentials($siteId)
{
    global $db;
    $key = $_SESSION['securityKey'];

    try {
        $query = $db->prepare("SELECT cf_email, cf_api_key FROM sites WHERE id = :id");
        $query->bindParam(':id', $siteId);
        $query->execute();

        $result = $query->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new Exception('Сайт не найден.');
        }

        $email = $result['cf_email'];
        $apiKey = decryptData($result['cf_api_key'], $key);

        if (!$email || !$apiKey) {
            throw new Exception('Не удалось получить учётные данные Cloudflare.');
        }

        return [
            'email' => $email,
            'apiKey' => $apiKey
        ];
    } catch (Exception $e) {
        throw $e;
    }
}

function checkCloudflareCredentials($siteId) {
    try {
        $credentials = getCloudflareCredentials($siteId);
        if (empty($credentials['email']) || empty($credentials['apiKey'])) {
            throw new Exception('Не настроены учетные данные Cloudflare');
        }
        return true;
    } catch (Exception $e) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

function sendCloudflareRequest($method, $endpoint, $data = [], $credentials, $isMultipart = false, $returnFullResponse = false) {
    $ch = curl_init();

    $url = 'https://api.cloudflare.com/client/v4' . $endpoint;

    if ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
    }

    $headers = [
        "X-Auth-Email: {$credentials['email']}",
        "X-Auth-Key: {$credentials['apiKey']}"
    ];

    // Добавляем Content-Type только если это не multipart запрос
    if (!$isMultipart) {
        $headers[] = "Content-Type: application/json";
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method !== 'GET' && !empty($data)) {
        if ($isMultipart) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('Ошибка cURL: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode >= 400 || !$result['success']) {
        $error = isset($result['errors'][0]['message']) ? $result['errors'][0]['message'] : 'Неизвестная ошибка';
        throw new Exception("Ошибка API Cloudflare: {$error}");
    }

    if ($returnFullResponse) {
        return $result;
    } else {
        return $result['result'];
    }
}

function addSiteToCloudflare($data) {
    try {
        if (!isset($data['siteId']) || !isset($data['domain'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $domain = $data['domain'];
        $credentials = getCloudflareCredentials($siteId);

        // 1. Добавление сайта
        $result = sendCloudflareRequest('POST', '/zones', ['name' => $domain, 'jump_start' => false], $credentials);
        $zoneId = $result['id'];

        // 2. Включение Flexible SSL
        try {
            sendCloudflareRequest('PATCH', "/zones/{$zoneId}/settings/ssl", ['value' => 'flexible'], $credentials);
        } catch (Exception $e) {
            error_log('Ошибка при включении SSL: ' . $e->getMessage());
        }

        // 3. Включение Always Use HTTPS
        try {
            enableAlwaysUseHTTPS($siteId, $zoneId);
        } catch (Exception $e) {
            error_log('Ошибка при включении Always Use HTTPS: ' . $e->getMessage());
        }

        // 4. Получаем полные данные о зоне
        $zoneDetails = sendCloudflareRequest('GET', "/zones/{$zoneId}", [], $credentials);
        $settings = sendCloudflareRequest('GET', "/zones/{$zoneId}/settings", [], $credentials);

        // Добавляем настройки в ответ
        foreach ($settings as $setting) {
            switch ($setting['id']) {
                case 'ssl':
                    $zoneDetails['ssl'] = $setting['value'];
                    break;
                case 'ech':
                    $zoneDetails['ech'] = $setting['value'];
                    break;
                case 'tls_1_3':
                    $zoneDetails['tls13'] = $setting['value'];
                    break;
            }
        }

        echo json_encode(['success' => true, 'zone' => $zoneDetails]);

    } catch (Exception $e) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function enableCloudflareAlwaysUseHTTPS($data)
{
    try {
        if (!isset($data['siteId'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];

        $credentials = getCloudflareCredentials($siteId);
        $zoneId = getCloudflareZoneId($siteId);

        $postData = [
            'value' => 'on'
        ];

        $result = sendCloudflareRequest('PATCH', "/zones/{$zoneId}/settings/always_use_https", $postData, $credentials);

        echo json_encode(['success' => true, 'result' => $result]);

    } catch (Exception $e) {
        error_log('Ошибка в enableCloudflareAlwaysUseHTTPS: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function importCloudflareDNSRecords($data) {
    try {
        if (!isset($data['siteId']) || !isset($data['zoneId']) || !isset($_FILES['file'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $zoneId = $data['zoneId'];
        $credentials = getCloudflareCredentials($siteId);

        // Создаем CURLFile из загруженного файла
        $file = new CURLFile(
            $_FILES['file']['tmp_name'],
            'text/plain',
            'dns_records.txt'
        );

        // Отправляем запрос в Cloudflare
        $result = sendCloudflareRequest(
            'POST',
            "/zones/{$zoneId}/dns_records/import",
            ['file' => $file, 'proxied' => 'true'],
            $credentials,
            true
        );

        echo json_encode(['success' => true, 'result' => $result]);

    } catch (Exception $e) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getCloudflareNSServers($data)
{
    try {
        if (!isset($data['siteId']) || !isset($data['zoneId'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $zoneId = $data['zoneId'];

        $credentials = getCloudflareCredentials($siteId);

        $zoneDetails = sendCloudflareRequest('GET', "/zones/{$zoneId}", [], $credentials);
        $nameServers = $zoneDetails['name_servers'];

        echo json_encode(['success' => true, 'name_servers' => $nameServers]);

    } catch (Exception $e) {
        error_log('Ошибка в getCloudflareNSServers: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function createCloudflareFirewallRule($data)
{
    try {
        if (!isset($data['siteId']) || !isset($data['expression']) || !isset($data['action']) || !isset($data['description'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $expression = $data['expression'];
        $action = $data['action'];
        $description = $data['description'];

        $credentials = getCloudflareCredentials($siteId);
        $zoneId = getCloudflareZoneId($siteId);

        // Создание фильтра
        $filterData = [
            [
                'expression' => $expression,
                'description' => $description
            ]
        ];

        $filters = sendCloudflareRequest('POST', "/zones/{$zoneId}/filters", $filterData, $credentials);
        $filterId = $filters[0]['id'];

        // Создание правила фаервола
        $ruleData = [
            [
                'filter' => ['id' => $filterId],
                'action' => $action,
                'description' => $description
            ]
        ];

        $rules = sendCloudflareRequest('POST', "/zones/{$zoneId}/firewall/rules", $ruleData, $credentials);

        echo json_encode(['success' => true, 'rule' => $rules]);

    } catch (Exception $e) {
        error_log('Ошибка в createCloudflareFirewallRule: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function toggleCloudflareFirewallRule($data)
{
    try {
        if (!isset($data['siteId']) || !isset($data['ruleId']) || !isset($data['paused'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $ruleId = $data['ruleId'];
        $paused = $data['paused']; // true или false

        $credentials = getCloudflareCredentials($siteId);
        $zoneId = getCloudflareZoneId($siteId);

        $postData = [
            'paused' => $paused
        ];

        $result = sendCloudflareRequest('PATCH', "/zones/{$zoneId}/firewall/rules/{$ruleId}", $postData, $credentials);

        echo json_encode(['success' => true, 'result' => $result]);

    } catch (Exception $e) {
        error_log('Ошибка в toggleCloudflareFirewallRule: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getCloudflareTrafficAnalytics($data)
{
    try {
        if (!isset($data['siteId'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];

        $since = isset($data['since']) ? $data['since'] : null;
        $until = isset($data['until']) ? $data['until'] : null;

        $now = new DateTime();
        if (!$since) {
            $since = $now->sub(new DateInterval('PT1H'))->format(DateTime::ATOM); // Последний час
        }
        if (!$until) {
            $until = $now->format(DateTime::ATOM);
        }

        $credentials = getCloudflareCredentials($siteId);
        $zoneId = getCloudflareZoneId($siteId);

        $params = [
            'since' => $since,
            'until' => $until,
            'continuous' => true
        ];

        $analytics = sendCloudflareRequest('GET', "/zones/{$zoneId}/analytics/dashboard", $params, $credentials);

        echo json_encode(['success' => true, 'analytics' => $analytics]);

    } catch (Exception $e) {
        error_log('Ошибка в getCloudflareTrafficAnalytics: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function checkCloudflareForDDoS($data)
{
    try {
        if (!isset($data['siteId'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];

        $credentials = getCloudflareCredentials($siteId);
        $zoneId = getCloudflareZoneId($siteId);

        $now = new DateTime();
        $since = $now->sub(new DateInterval('PT1H'))->format(DateTime::ATOM); // Последний час
        $until = $now->format(DateTime::ATOM);

        $params = [
            'since' => $since,
            'until' => $until,
            'continuous' => true
        ];

        $analytics = sendCloudflareRequest('GET', "/zones/{$zoneId}/analytics/dashboard", $params, $credentials);

        $requests = $analytics['totals']['requests']['all'];
        $threats = $analytics['totals']['threats']['all'];

        // Простая логика: если количество угроз превышает определенный порог, возможно DDoS-атака
        $ddosDetected = false;
        if ($threats > 1000) {
            $ddosDetected = true;
        }

        echo json_encode([
            'success' => true,
            'ddosDetected' => $ddosDetected,
            'totalRequests' => $requests,
            'totalThreats' => $threats,
            'analytics' => $analytics
        ]);

    } catch (Exception $e) {
        error_log('Ошибка в checkCloudflareForDDoS: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getCloudflareTLS13Status($data)
{
    try {
        if (!isset($data['siteId'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];

        $credentials = getCloudflareCredentials($siteId);
        $zoneId = getCloudflareZoneId($siteId);

        $result = sendCloudflareRequest('GET', "/zones/{$zoneId}/settings/tls_1_3", [], $credentials);

        echo json_encode(['success' => true, 'result' => $result]);

    } catch (Exception $e) {
        error_log('Ошибка в getCloudflareTLS13Status: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function removeSiteFromCloudflare($data) {
    try {
        if (!isset($data['siteId']) || !isset($data['zoneId'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $zoneId = $data['zoneId'];
        $credentials = getCloudflareCredentials($siteId);

        // Удаление зоны из Cloudflare
        $result = sendCloudflareRequest('DELETE', "/zones/{$zoneId}", [], $credentials);

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        error_log('Ошибка в removeSiteFromCloudflare: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function setCloudflareECHStatus($data)
{
    try {
        if (!isset($data['siteId']) || !isset($data['zoneId']) || !isset($data['value'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $zoneId = $data['zoneId'];
        $value = $data['value'];

        if ($value !== 'on' && $value !== 'off') {
            throw new Exception('Некорректное значение параметра value. Допустимые значения: "on", "off"');
        }

        $credentials = getCloudflareCredentials($siteId);

        $postData = [
            'value' => $value
        ];

        $result = sendCloudflareRequest('PATCH', "/zones/{$zoneId}/settings/ech", $postData, $credentials);

        echo json_encode(['success' => true, 'result' => $result]);

    } catch (Exception $e) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function setCloudflareTLS13Status($data)
{
    try {
        if (!isset($data['siteId']) || !isset($data['zoneId']) || !isset($data['value'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $zoneId = $data['zoneId'];
        $value = $data['value'];

        if ($value !== 'on' && $value !== 'off') {
            throw new Exception('Некорректное значение параметра value. Допустимые значения: "on", "off"');
        }

        $credentials = getCloudflareCredentials($siteId);

        $postData = [
            'value' => $value
        ];

        $result = sendCloudflareRequest('PATCH', "/zones/{$zoneId}/settings/tls_1_3", $postData, $credentials);

        echo json_encode(['success' => true, 'result' => $result]);

    } catch (Exception $e) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function enableAlwaysUseHTTPS($siteId, $zoneId) {
    $credentials = getCloudflareCredentials($siteId);

    $postData = [
        'value' => 'on'
    ];

    return sendCloudflareRequest('PATCH', "/zones/{$zoneId}/settings/always_use_https", $postData, $credentials);
}

function setCloudflareSSLMode($data) {
    try {
        if (!isset($data['siteId']) || !isset($data['zoneId']) || !isset($data['mode'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $zoneId = $data['zoneId'];
        $mode = $data['mode'];

        $allowedModes = ['off', 'flexible', 'full', 'strict'];
        if (!in_array($mode, $allowedModes)) {
            throw new Exception('Недопустимый режим SSL');
        }

        $credentials = getCloudflareCredentials($siteId);

        // Установка SSL mode
        $result = sendCloudflareRequest('PATCH', "/zones/{$zoneId}/settings/ssl", ['value' => $mode], $credentials);

        // Если включаем SSL, автоматически включаем HTTPS
        if ($mode !== 'off') {
            try {
                enableAlwaysUseHTTPS($siteId, $zoneId);
            } catch (Exception $e) {
                // Логируем ошибку, но не прерываем выполнение
                error_log('Ошибка при включении Always Use HTTPS: ' . $e->getMessage());
            }
        }

        echo json_encode(['success' => true, 'result' => $result]);

    } catch (Exception $e) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getCloudflareZonesList($data)
{
    try {
        if (!isset($data['siteId'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $credentials = getCloudflareCredentials($data['siteId']);

        $allZones = [];
        $page = 1;
        $perPage = 50; // Максимальное значение per_page для Cloudflare API
        $totalPages = 1;

        do {
            // Запрашиваем страницу с зонами, используя $returnFullResponse = true
            $response = sendCloudflareRequest('GET', '/zones', [
                'page' => $page,
                'per_page' => $perPage
            ], $credentials, false, true);

            // Добавляем полученные зоны в общий массив
            $allZones = array_merge($allZones, $response['result']);

            // Получаем информацию о пагинации
            $resultInfo = $response['result_info'];
            $totalPages = $resultInfo['total_pages'];

            $page++;
        } while ($page <= $totalPages);

        // Теперь имеем полный список зон в $allZones

        // Продолжаем с получением настроек зон, как и раньше
        $mh = curl_multi_init();
        $channels = [];

        foreach ($allZones as $index => $zone) {
            $ch = curl_init();

            $url = 'https://api.cloudflare.com/client/v4/zones/' . $zone['id'] . '/settings';

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-Auth-Email: {$credentials['email']}",
                "X-Auth-Key: {$credentials['apiKey']}",
                "Content-Type: application/json"
            ]);

            $channels[$index] = $ch;
            curl_multi_add_handle($mh, $ch);
        }

        // Выполняем параллельные запросы
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        // Обрабатываем результаты
        foreach ($channels as $index => $ch) {
            $response = curl_multi_getcontent($ch);
            $result = json_decode($response, true);

            if (isset($result['result'])) {
                foreach ($result['result'] as $setting) {
                    switch ($setting['id']) {
                        case 'ssl':
                            $allZones[$index]['ssl'] = $setting['value'];
                            break;
                        case 'ech':
                            $allZones[$index]['ech'] = $setting['value'];
                            break;
                        case 'tls_1_3':
                            $allZones[$index]['tls13'] = $setting['value'];
                            break;
                    }
                }
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);

        echo json_encode(['success' => true, 'zones' => $allZones]);

    } catch (Exception $e) {
        error_log('Ошибка в getCloudflareZonesList: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getDNSRecords($data) {
    try {
        if (!isset($data['siteId']) || !isset($data['zoneId'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $zoneId = $data['zoneId'];

        $credentials = getCloudflareCredentials($siteId);

        // Получаем А-записи для зоны
        $dnsRecords = sendCloudflareRequest('GET', "/zones/{$zoneId}/dns_records", [
            'type' => 'A',
            'per_page' => 100 // Increase limit if necessary
        ], $credentials);

        // Получаем имя зоны
        $zoneDetails = sendCloudflareRequest('GET', "/zones/{$zoneId}", [], $credentials);
        $zoneName = $zoneDetails['name'];

        // Фильтруем необходимые записи
        $filteredRecords = array_filter($dnsRecords, function($record) use ($zoneName) {
            return $record['name'] === $zoneName || $record['name'] === 'www.' . $zoneName;
        });

        // Считаем частоту использования IP-адресов
        $ipCounts = [];
        foreach ($dnsRecords as $record) {
            if (isset($record['content']) && filter_var($record['content'], FILTER_VALIDATE_IP)) {
                $ip = $record['content'];
                $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;
            }
        }

        // Находим IP с максимальной частотой
        $mostUsedIP = array_keys($ipCounts, max($ipCounts))[0] ?? null;

        echo json_encode([
            'success' => true,
            'dns_records' => array_values($filteredRecords),
            'most_used_ip' => $mostUsedIP
        ]);

    } catch (Exception $e) {
        error_log('Ошибка в getDNSRecords: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function updateDNSRecord($data) {
    try {
        if (!isset($data['siteId']) || !isset($data['zoneId']) || !isset($data['recordId']) || !isset($data['content']) || !isset($data['proxied'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $zoneId = $data['zoneId'];
        $recordId = $data['recordId'];
        $content = $data['content']; // Новый IP-адрес
        $proxied = filter_var($data['proxied'], FILTER_VALIDATE_BOOLEAN); // Преобразуем строку 'true'/'false' в boolean

        $credentials = getCloudflareCredentials($siteId);

        // Получаем текущие данные записи
        $existingRecord = sendCloudflareRequest('GET', "/zones/{$zoneId}/dns_records/{$recordId}", [], $credentials);

        // Обновляем необходимые поля
        $updatedRecord = [
            'type' => $existingRecord['type'],
            'name' => $existingRecord['name'],
            'content' => $content,
            'ttl' => $existingRecord['ttl'],
            'proxied' => $proxied
        ];

        // Отправляем запрос на обновление
        $result = sendCloudflareRequest('PUT', "/zones/{$zoneId}/dns_records/{$recordId}", $updatedRecord, $credentials);

        echo json_encode(['success' => true, 'dns_record' => $result]);

    } catch (Exception $e) {
        error_log('Ошибка в updateDNSRecord: ' . $e->getMessage());
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function updateDNSRecords($data) {
    $debugInfo = [
        'received_data' => $data,
        'steps' => [],
        'errors' => [],
        'dns_records_raw' => null, // Сырые записи до фильтрации
        'filtered_records' => [] // Отфильтрованные записи
    ];

    try {
        // Проверяем наличие необходимых параметров
        if (!isset($data['siteId']) || !isset($data['zoneId']) || !isset($data['content']) || !isset($data['proxied'])) {
            throw new Exception('Необходимые параметры не переданы');
        }

        $siteId = $data['siteId'];
        $zoneId = $data['zoneId'];
        $content = $data['content'];
        $proxied = filter_var($data['proxied'], FILTER_VALIDATE_BOOLEAN);

        $debugInfo['steps'][] = "Параметры успешно получены";

        // Получаем учётные данные Cloudflare
        $credentials = getCloudflareCredentials($siteId);
        $debugInfo['credentials'] = $credentials;

        // Получаем имя зоны
        $zoneDetails = sendCloudflareRequest('GET', "/zones/{$zoneId}", [], $credentials);
        if (isset($zoneDetails['success']) && !$zoneDetails['success']) {
            throw new Exception('Ошибка получения данных зоны: ' . json_encode($zoneDetails));
        }
        $zoneName = $zoneDetails['name'];
        $debugInfo['zone_details'] = $zoneDetails;

        // Получаем все записи типа A
        $dnsRecords = sendCloudflareRequest('GET', "/zones/{$zoneId}/dns_records", [
            'type' => 'A',
            'per_page' => 100
        ], $credentials);

        // Сохраняем сырые записи для анализа
        $debugInfo['dns_records_raw'] = $dnsRecords;

        // Проверяем, вернулись ли записи
        if (!is_array($dnsRecords) || empty($dnsRecords)) {
            throw new Exception("Не удалось получить A-записи для зоны {$zoneName}");
        }

        // Фильтруем только нужные записи: корневая и www
        $filteredRecords = array_filter($dnsRecords, function ($record) use ($zoneName, &$debugInfo) {
            $isRoot = $record['name'] === $zoneName; // Проверка для корневой записи
            $isWWW = $record['name'] === "www.{$zoneName}"; // Проверка для www

            // Логируем результаты фильтрации
            $debugInfo['steps'][] = [
                'record_name' => $record['name'],
                'is_root' => $isRoot,
                'is_www' => $isWWW
            ];

            return $isRoot || $isWWW;
        });

        // Сохраняем отфильтрованные записи
        $debugInfo['filtered_records'] = array_values($filteredRecords);

        // Проверяем, найдены ли нужные записи
        if (empty($filteredRecords)) {
            throw new Exception("Не найдены корневая запись или запись www для зоны {$zoneName}");
        }

        // Обновляем записи
        $updatedRecords = [];
        foreach ($filteredRecords as $record) {
            $updatedRecord = [
                'type' => $record['type'],
                'name' => $record['name'],
                'content' => $content,
                'ttl' => $record['ttl'],
                'proxied' => $proxied
            ];

            $debugInfo['steps'][] = "Обновление записи: " . json_encode($updatedRecord);

            $result = sendCloudflareRequest('PUT', "/zones/{$zoneId}/dns_records/{$record['id']}", $updatedRecord, $credentials);

            if (isset($result['success']) && !$result['success']) {
                $debugInfo['errors'][] = "Ошибка обновления записи {$record['name']}: " . json_encode($result);
            } else {
                $updatedRecords[] = $result;
            }
        }

        // Возвращаем успешный результат
        echo json_encode([
            'success' => true,
            'updated_records' => $updatedRecords,
        ]);
    } catch (Exception $e) {
        $debugInfo['errors'][] = $e->getMessage();

        header('HTTP/1.1 400 Bad Request');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => $debugInfo
        ]);
    }
}