<?php

declare(strict_types=1);

require_once __DIR__ . '/commerce.php';

function gawdee_http_request(string $method, string $url, array $headers = [], array|string|null $body = null, int $timeout = 30, ?array $basicAuth = null, string $bodyMode = 'json'): array
{
    if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
        throw new RuntimeException('The integration endpoint is not a valid HTTP address.');
    }

    $handle = curl_init($url);
    if ($handle === false) {
        throw new RuntimeException('Unable to initialize the HTTP client.');
    }

    $requestHeaders = array_merge(['Accept: application/json'], $headers);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_HTTPHEADER => $requestHeaders,
    ];
    if ($body !== null) {
        if (is_string($body)) {
            $options[CURLOPT_POSTFIELDS] = $body;
        } elseif ($bodyMode === 'form') {
            $options[CURLOPT_POSTFIELDS] = http_build_query($body, '', '&', PHP_QUERY_RFC3986);
        } else {
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
    }
    if ($basicAuth) {
        $options[CURLOPT_USERPWD] = $basicAuth[0] . ':' . $basicAuth[1];
        $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
    }
    curl_setopt_array($handle, $options);

    $raw = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    if ($raw === false) {
        throw new RuntimeException('Integration request failed: ' . $error);
    }

    $decoded = json_decode((string) $raw, true);
    $response = is_array($decoded) ? $decoded : ['raw' => mb_substr((string) $raw, 0, 3000)];
    if ($status < 200 || $status >= 300) {
        $message = $response['error']['description'] ?? $response['error']['message'] ?? $response['message'] ?? 'Remote service returned HTTP ' . $status;
        throw new RuntimeException(is_string($message) ? $message : 'Remote integration request failed.');
    }

    return ['status' => $status, 'data' => $response];
}

function gawdee_razorpay_configured(): bool
{
    return gawdee_setting('razorpay_key_id') !== '' && gawdee_setting('razorpay_key_secret') !== '';
}

function gawdee_razorpay_create_order(int $amountInRupees, string $receipt, array $notes = []): array
{
    if (!gawdee_razorpay_configured()) {
        throw new RuntimeException('Razorpay is not configured yet. Add the Key ID and Key Secret in Admin > Integrations.');
    }

    // Razorpay Orders API: https://razorpay.com/docs/api/orders/create/
    $response = gawdee_http_request(
        'POST',
        'https://api.razorpay.com/v1/orders',
        ['Content-Type: application/json'],
        [
            'amount' => $amountInRupees * 100,
            'currency' => 'INR',
            'receipt' => $receipt,
            'notes' => $notes,
        ],
        30,
        [gawdee_setting('razorpay_key_id'), gawdee_setting('razorpay_key_secret')]
    );
    $data = $response['data'];
    if (empty($data['id'])) {
        throw new RuntimeException('Razorpay did not return an order ID.');
    }
    gawdee_log_integration('razorpay', 'create_order', 'success', 'Payment order created.', (string) $data['id']);
    return $data;
}

function gawdee_razorpay_verify_payment(string $serverOrderId, string $paymentId, string $signature): bool
{
    // Razorpay Standard Checkout verification: https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/integration-steps/
    $secret = gawdee_setting('razorpay_key_secret');
    if ($secret === '' || $serverOrderId === '' || $paymentId === '' || $signature === '') {
        return false;
    }
    $expected = hash_hmac('sha256', $serverOrderId . '|' . $paymentId, $secret);
    return hash_equals($expected, $signature);
}

function gawdee_razorpay_verify_webhook(string $rawBody, string $signature): bool
{
    // Razorpay webhook signature validation: https://razorpay.com/docs/webhooks/validate-test/
    $secret = gawdee_setting('razorpay_webhook_secret');
    if ($secret === '' || $signature === '') {
        return false;
    }
    return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
}

function gawdee_razorpay_fetch_payment(string $paymentId): array
{
    if (!gawdee_razorpay_configured() || !preg_match('/^pay_[A-Za-z0-9]+$/', $paymentId)) {
        throw new RuntimeException('The Razorpay payment reference is invalid.');
    }
    $response = gawdee_http_request(
        'GET',
        'https://api.razorpay.com/v1/payments/' . rawurlencode($paymentId),
        [],
        null,
        30,
        [gawdee_setting('razorpay_key_id'), gawdee_setting('razorpay_key_secret')]
    );
    return $response['data'];
}

function gawdee_razorpay_payment_matches_order(array $payment, array $order): bool
{
    return hash_equals((string) $order['razorpay_order_id'], (string) ($payment['order_id'] ?? ''))
        && (int) ($payment['amount'] ?? -1) === (int) $order['total'] * 100
        && strtoupper((string) ($payment['currency'] ?? '')) === 'INR'
        && (string) ($payment['status'] ?? '') === 'captured';
}

function gawdee_ai_configured(?string $provider = null): bool
{
    $provider ??= gawdee_setting('ai_provider', 'groq');
    return $provider === 'openai'
        ? gawdee_setting('openai_api_key') !== ''
        : gawdee_setting('groq_api_key') !== '';
}

function gawdee_ai_generate(string $instructions, string $input, int $maxTokens = 1100, ?string $provider = null): string
{
    $provider ??= gawdee_setting('ai_provider', 'groq');
    if (!in_array($provider, ['groq', 'openai'], true)) {
        throw new RuntimeException('Select Groq or OpenAI as the AI provider.');
    }
    if (!gawdee_ai_configured($provider)) {
        throw new RuntimeException(ucfirst($provider) . ' is not configured. Add its API key in Admin > AI & Blog.');
    }

    if ($provider === 'openai') {
        $response = gawdee_http_request(
            'POST',
            'https://api.openai.com/v1/responses',
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . gawdee_setting('openai_api_key'),
            ],
            [
                'model' => gawdee_setting('openai_model', 'gpt-5.6-luna'),
                'instructions' => $instructions,
                'input' => $input,
                'max_output_tokens' => $maxTokens,
                'store' => false,
            ],
            90
        );
        $text = gawdee_openai_output_text($response['data']);
    } else {
        $response = gawdee_http_request(
            'POST',
            'https://api.groq.com/openai/v1/chat/completions',
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . gawdee_setting('groq_api_key'),
            ],
            [
                'model' => gawdee_setting('groq_model', 'llama-3.3-70b-versatile'),
                'messages' => [
                    ['role' => 'system', 'content' => $instructions],
                    ['role' => 'user', 'content' => $input],
                ],
                'temperature' => 0.65,
                'max_completion_tokens' => $maxTokens,
            ],
            90
        );
        $text = (string) ($response['data']['choices'][0]['message']['content'] ?? '');
    }

    if (trim($text) === '') {
        throw new RuntimeException('The AI provider returned an empty response.');
    }
    gawdee_log_integration($provider, 'generate_text', 'success', 'Text generation completed.');
    return trim($text);
}

function gawdee_openai_output_text(array $response): string
{
    if (!empty($response['output_text']) && is_string($response['output_text'])) {
        return $response['output_text'];
    }
    $parts = [];
    foreach (($response['output'] ?? []) as $item) {
        foreach (($item['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                $parts[] = (string) $content['text'];
            }
        }
    }
    return implode("\n", $parts);
}

function gawdee_generate_blog(string $topic, string $status = 'draft'): array
{
    $provider = gawdee_setting('ai_provider', 'groq');
    $products = array_map(static fn(array $product): string => $product['full_name'] . ': ' . $product['description'], gawdee_products());
    $instructions = <<<'PROMPT'
You are Gawdee's responsible food and wellness editor. Write accurate, helpful Indian consumer content. Avoid medical claims, disease-treatment language, fabricated research, and guaranteed outcomes. Never claim organic certification unless the supplied product data explicitly says so. Return only valid JSON with keys: title, excerpt, meta_description, content_html. content_html must use only h2, h3, p, ul, ol, li, strong, em, and blockquote tags. The article should be original, readable, practical, and 700-1000 words.
PROMPT;
    $input = "Topic: {$topic}\n\nApproved Gawdee product context:\n" . implode("\n", $products);
    $raw = gawdee_ai_generate($instructions, $input, 2400, $provider);
    $raw = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($raw)) ?? $raw;
    $article = json_decode($raw, true);
    if (!is_array($article) || empty($article['title']) || empty($article['content_html'])) {
        throw new RuntimeException('The AI response was not a valid blog document. Try a more specific topic.');
    }

    $title = trim(mb_substr((string) $article['title'], 0, 180));
    $baseSlug = gawdee_slug($title);
    $slug = $baseSlug;
    $counter = 2;
    $check = gawdee_db()->prepare('SELECT COUNT(*) FROM blog_posts WHERE slug = ?');
    do {
        $check->execute([$slug]);
        if ((int) $check->fetchColumn() === 0) {
            break;
        }
        $slug = $baseSlug . '-' . $counter++;
    } while ($counter < 100);

    $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
    $statement = gawdee_db()->prepare(<<<'SQL'
INSERT INTO blog_posts (title, slug, excerpt, content, status, source, ai_provider, meta_description, published_at)
VALUES (?, ?, ?, ?, ?, 'ai', ?, ?, ?)
SQL);
    $statement->execute([
        $title,
        $slug,
        trim(mb_substr((string) ($article['excerpt'] ?? ''), 0, 360)),
        gawdee_sanitize_article_html((string) $article['content_html']),
        $status,
        $provider,
        trim(mb_substr((string) ($article['meta_description'] ?? ''), 0, 180)),
        $publishedAt,
    ]);
    gawdee_set_setting('ai_last_blog_at', date(DATE_ATOM));

    return ['id' => (int) gawdee_db()->lastInsertId(), 'title' => $title, 'slug' => $slug, 'status' => $status];
}

function gawdee_sanitize_article_html(string $html): string
{
    $html = strip_tags($html, '<h2><h3><p><ul><ol><li><strong><em><blockquote>');
    $html = preg_replace('/\s+(?:on\w+|style|class|id)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
    return trim($html);
}

function gawdee_dtdc_configured(): bool
{
    return gawdee_setting('dtdc_enabled', '0') === '1' && gawdee_setting('dtdc_booking_endpoint') !== '' && (
        gawdee_setting('dtdc_api_token') !== '' ||
        (gawdee_setting('dtdc_username') !== '' && gawdee_setting('dtdc_password') !== '')
    );
}

function gawdee_dtdc_create_shipment(array $order, array $items): array
{
    if (!gawdee_dtdc_configured()) {
        throw new RuntimeException('DTDC is not configured. Add your merchant-issued endpoint and credentials in Admin > Integrations.');
    }

    $endpoint = gawdee_setting('dtdc_booking_endpoint');
    $token = gawdee_setting('dtdc_api_token');
    $headerName = trim(gawdee_setting('dtdc_auth_header', 'Authorization')) ?: 'Authorization';
    $prefix = gawdee_setting('dtdc_auth_prefix', 'Bearer');
    $headers = ['Content-Type: application/json'];
    if ($token !== '') {
        $headers[] = $headerName . ': ' . trim($prefix . ' ' . $token);
    }

    $weight = max(0.5, count($items) * 0.5);
    $payload = [
        'customer_code' => gawdee_setting('dtdc_customer_code'),
        'reference_number' => $order['order_number'],
        'service_type' => gawdee_setting('dtdc_service_type', 'EXPRESS'),
        'shipment_type' => $order['payment_method'] === 'cod' ? 'COD' : 'PREPAID',
        'cod_amount' => $order['payment_method'] === 'cod' ? (int) $order['total'] : 0,
        'pickup_pincode' => gawdee_setting('dtdc_pickup_pincode'),
        'consignee' => [
            'name' => $order['customer_name'],
            'phone' => $order['phone'],
            'email' => $order['email'],
            'address1' => $order['address1'],
            'address2' => $order['address2'],
            'city' => $order['city'],
            'state' => $order['state'],
            'pincode' => $order['pincode'],
            'country' => 'IN',
        ],
        'pieces' => array_map(static fn(array $item): array => [
            'sku' => $item['product_id'],
            'description' => $item['product_name'],
            'quantity' => (int) $item['quantity'],
            'declared_value' => (int) $item['unit_price'] * (int) $item['quantity'],
        ], $items),
        'weight_kg' => $weight,
    ];

    $template = trim(gawdee_setting('dtdc_payload_template'));
    if ($template !== '') {
        $payload = gawdee_dtdc_apply_template($template, $order, $items);
    }

    $basicAuth = null;
    if ($token === '' && gawdee_setting('dtdc_username') !== '') {
        $basicAuth = [gawdee_setting('dtdc_username'), gawdee_setting('dtdc_password')];
    }

    try {
        $response = gawdee_http_request('POST', $endpoint, $headers, $payload, 45, $basicAuth);
        $data = $response['data'];
        $reference = (string) ($data['awb_number'] ?? $data['awb'] ?? $data['consignment_number'] ?? $data['reference_number'] ?? $data['data']['awb_number'] ?? '');
        if ($reference === '') {
            throw new RuntimeException('DTDC accepted the request but no AWB/consignment number was found in its response.');
        }
        $trackingUrl = str_replace(['{awb}', '{reference}'], rawurlencode($reference), gawdee_setting('dtdc_tracking_endpoint'));
        gawdee_log_integration('dtdc', 'create_shipment', 'success', 'Shipment created.', $reference);
        return ['reference' => $reference, 'tracking_url' => $trackingUrl, 'response' => $data];
    } catch (Throwable $error) {
        gawdee_log_integration('dtdc', 'create_shipment', 'failed', $error->getMessage(), (string) $order['order_number']);
        throw $error;
    }
}

function gawdee_dtdc_apply_template(string $template, array $order, array $items): array
{
    $replacements = [
        '{{order_number}}' => (string) $order['order_number'],
        '{{customer_code}}' => gawdee_setting('dtdc_customer_code'),
        '{{customer_name}}' => (string) $order['customer_name'],
        '{{email}}' => (string) $order['email'],
        '{{phone}}' => (string) $order['phone'],
        '{{address1}}' => (string) $order['address1'],
        '{{address2}}' => (string) $order['address2'],
        '{{city}}' => (string) $order['city'],
        '{{state}}' => (string) $order['state'],
        '{{pincode}}' => (string) $order['pincode'],
        '{{amount}}' => (string) $order['total'],
        '{{payment_method}}' => (string) $order['payment_method'],
        '{{items_json}}' => json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ];
    $decoded = json_decode(strtr($template, $replacements), true);
    if (!is_array($decoded)) {
        throw new RuntimeException('The DTDC payload template is not valid JSON after placeholder replacement.');
    }
    return $decoded;
}

function gawdee_delhivery_configured(): bool
{
    foreach (['delhivery_api_token', 'delhivery_pickup_location', 'delhivery_origin_phone', 'delhivery_origin_address', 'delhivery_origin_city', 'delhivery_origin_state', 'delhivery_origin_pincode'] as $key) {
        if (trim(gawdee_setting($key)) === '') {
            return false;
        }
    }
    return gawdee_setting('delhivery_enabled', '0') === '1';
}

function gawdee_delhivery_base_url(): string
{
    return gawdee_setting('delhivery_environment', 'staging') === 'production'
        ? 'https://track.delhivery.com'
        : 'https://staging-express.delhivery.com';
}

function gawdee_delhivery_headers(string $contentType = 'application/json'): array
{
    return [
        'Authorization: Token ' . gawdee_setting('delhivery_api_token'),
        'Content-Type: ' . $contentType,
    ];
}

function gawdee_delhivery_serviceability(string $pincode, string $paymentMode = 'Prepaid'): array
{
    // Delhivery B2C serviceability/manifest workflow: https://one.delhivery.com/developer-portal/documents/b2c/
    if (!gawdee_delhivery_configured()) {
        throw new RuntimeException('Delhivery is not configured. Add the API token and warehouse details in Admin > Integrations.');
    }
    if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
        throw new RuntimeException('A valid six-digit Indian delivery pincode is required.');
    }
    $url = gawdee_delhivery_base_url() . '/c/api/pin-codes/json/?filter_codes=' . rawurlencode($pincode);
    $response = gawdee_http_request('GET', $url, gawdee_delhivery_headers(), null, 30);
    $postal = $response['data']['delivery_codes'][0]['postal_code'] ?? null;
    if (!is_array($postal)) {
        throw new RuntimeException('Delhivery does not currently service this pincode.');
    }
    $remarks = strtolower((string) ($postal['remarks'] ?? ''));
    if (str_contains($remarks, 'embargo')) {
        throw new RuntimeException('Delhivery has temporarily embargoed this pincode.');
    }
    $field = strtolower($paymentMode) === 'cod' ? 'cod' : 'pre_paid';
    $available = strtoupper((string) ($postal[$field] ?? $postal[$field === 'cod' ? 'cash' : 'prepaid'] ?? ''));
    if ($available !== '' && !in_array($available, ['Y', 'YES', 'TRUE', '1'], true)) {
        throw new RuntimeException('Delhivery does not support ' . ($field === 'cod' ? 'cash on delivery' : 'prepaid delivery') . ' for this pincode.');
    }
    return $postal;
}

function gawdee_delhivery_build_payload(array $order, array $items): array
{
    $quantity = 0;
    $descriptions = [];
    foreach ($items as $item) {
        $quantity += max(1, (int) ($item['quantity'] ?? 1));
        $descriptions[] = trim((string) ($item['product_name'] ?? $item['product_id'] ?? 'Product'));
    }
    $quantity = max(1, $quantity);
    $weight = max(100, (int) gawdee_setting('delhivery_default_weight_grams', '500')) * $quantity;
    $address = trim(implode(', ', array_filter([(string) $order['address1'], (string) ($order['address2'] ?? '')])));
    $paymentMode = ($order['payment_method'] ?? '') === 'cod' ? 'COD' : 'Prepaid';
    $originAddress = trim(gawdee_setting('delhivery_origin_address'));

    return [
        'shipments' => [[
            'name' => (string) $order['customer_name'],
            'add' => $address,
            'pin' => (string) $order['pincode'],
            'city' => (string) $order['city'],
            'state' => (string) $order['state'],
            'country' => 'India',
            'phone' => gawdee_normalize_phone((string) $order['phone']),
            'order' => (string) $order['order_number'],
            'payment_mode' => $paymentMode,
            'return_pin' => gawdee_setting('delhivery_origin_pincode'),
            'return_city' => gawdee_setting('delhivery_origin_city'),
            'return_phone' => gawdee_normalize_phone(gawdee_setting('delhivery_origin_phone')),
            'return_add' => $originAddress,
            'return_state' => gawdee_setting('delhivery_origin_state'),
            'return_country' => 'India',
            'products_desc' => mb_substr(implode(', ', array_filter($descriptions)), 0, 500),
            'cod_amount' => $paymentMode === 'COD' ? (int) $order['total'] : 0,
            'order_date' => (string) ($order['created_at'] ?? date('Y-m-d H:i:s')),
            'total_amount' => (int) $order['total'],
            'seller_add' => $originAddress,
            'seller_name' => gawdee_setting('store_name', 'Gawdee'),
            'seller_inv' => (string) $order['order_number'],
            'quantity' => $quantity,
            'waybill' => '',
            'weight' => $weight,
            'shipment_length' => max(1, (int) gawdee_setting('delhivery_default_length_cm', '20')),
            'shipment_width' => max(1, (int) gawdee_setting('delhivery_default_width_cm', '15')),
            'shipment_height' => max(1, (int) gawdee_setting('delhivery_default_height_cm', '10')),
            'shipping_mode' => 'Surface',
            'address_type' => 'home',
        ]],
        'pickup_location' => [
            'name' => gawdee_setting('delhivery_pickup_location'),
            'add' => $originAddress,
            'city' => gawdee_setting('delhivery_origin_city'),
            'state' => gawdee_setting('delhivery_origin_state'),
            'pin_code' => gawdee_setting('delhivery_origin_pincode'),
            'country' => 'India',
            'phone' => gawdee_normalize_phone(gawdee_setting('delhivery_origin_phone')),
        ],
    ];
}

function gawdee_delhivery_tracking_url(string $waybill): string
{
    return 'https://www.delhivery.com/track/package/' . rawurlencode($waybill);
}

function gawdee_delhivery_create_shipment(array $order, array $items): array
{
    if (!gawdee_delhivery_configured()) {
        throw new RuntimeException('Delhivery is offline or incomplete in Admin > Integrations.');
    }
    gawdee_delhivery_serviceability((string) $order['pincode'], ($order['payment_method'] ?? '') === 'cod' ? 'COD' : 'Prepaid');
    $payload = gawdee_delhivery_build_payload($order, $items);
    try {
        $response = gawdee_http_request(
            'POST',
            gawdee_delhivery_base_url() . '/api/cmu/create.json',
            gawdee_delhivery_headers('application/x-www-form-urlencoded'),
            ['format' => 'json', 'data' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)],
            45,
            null,
            'form'
        );
        $data = $response['data'];
        $package = $data['packages'][0] ?? [];
        $waybill = trim((string) ($package['waybill'] ?? $data['waybill'] ?? ''));
        if ($waybill === '') {
            $reason = (string) ($package['remarks'][0] ?? $package['remarks'] ?? $data['rmk'] ?? 'Delhivery accepted the request but did not return a waybill.');
            throw new RuntimeException($reason);
        }
        gawdee_log_integration('delhivery', 'create_shipment', 'success', 'Shipment manifested.', $waybill);
        return [
            'waybill' => $waybill,
            'tracking_url' => gawdee_delhivery_tracking_url($waybill),
            'label_url' => 'delhivery-label.php?order=' . (int) $order['id'],
            'response' => $data,
        ];
    } catch (Throwable $error) {
        gawdee_log_integration('delhivery', 'create_shipment', 'failed', $error->getMessage(), (string) $order['order_number']);
        throw $error;
    }
}

function gawdee_delhivery_track(string $waybill): array
{
    // Delhivery order tracking: https://one.delhivery.com/developer-portal/document/b2c/detail/order-tracking
    if (!gawdee_delhivery_configured() || !preg_match('/^[A-Za-z0-9_-]{5,60}$/', $waybill)) {
        throw new RuntimeException('A valid configured Delhivery waybill is required.');
    }
    $response = gawdee_http_request(
        'GET',
        gawdee_delhivery_base_url() . '/api/v1/packages/json/?waybill=' . rawurlencode($waybill),
        gawdee_delhivery_headers(),
        null,
        30
    );
    $shipment = $response['data']['ShipmentData'][0]['Shipment'] ?? null;
    if (!is_array($shipment)) {
        throw new RuntimeException('Delhivery returned no tracking record for this waybill.');
    }
    $status = (string) ($shipment['Status']['Status'] ?? $shipment['Status']['StatusType'] ?? 'Unknown');
    return [
        'status' => $status,
        'status_date' => (string) ($shipment['Status']['StatusDateTime'] ?? ''),
        'location' => (string) ($shipment['Status']['StatusLocation'] ?? $shipment['Destination'] ?? ''),
        'shipment' => $shipment,
    ];
}

function gawdee_delhivery_map_order_status(string $status): ?string
{
    $status = strtolower(trim($status));
    if ($status === '') {
        return null;
    }
    if (str_contains($status, 'deliver') && !str_contains($status, 'undeliver') && !str_contains($status, 'out for')) {
        return 'delivered';
    }
    if (str_contains($status, 'cancel') || str_contains($status, 'rto') || str_contains($status, 'return')) {
        return 'on_hold';
    }
    foreach (['transit', 'dispatch', 'picked', 'out for delivery'] as $needle) {
        if (str_contains($status, $needle)) {
            return 'shipped';
        }
    }
    foreach (['manifest', 'pending', 'ready', 'bagged'] as $needle) {
        if (str_contains($status, $needle)) {
            return 'packed';
        }
    }
    return null;
}

function gawdee_delhivery_sync_order(int $orderId): array
{
    $order = gawdee_order_by_id($orderId);
    if (!$order || trim((string) ($order['delhivery_waybill'] ?? '')) === '') {
        throw new RuntimeException('This order has no Delhivery waybill.');
    }
    $tracking = gawdee_delhivery_track((string) $order['delhivery_waybill']);
    $mapped = gawdee_delhivery_map_order_status($tracking['status']);
    gawdee_db()->prepare('UPDATE orders SET delhivery_last_status=?, delhivery_last_sync_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?')
        ->execute([$tracking['status'], $orderId]);
    if ($mapped !== null && $mapped !== $order['status'] && in_array($mapped, gawdee_order_allowed_transitions($order), true)) {
        gawdee_update_order_status($orderId, $mapped, 'Updated from Delhivery: ' . $tracking['status'] . ($tracking['location'] !== '' ? ' at ' . $tracking['location'] : '') . '.');
    }
    gawdee_log_integration('delhivery', 'track_shipment', 'success', $tracking['status'], (string) $order['delhivery_waybill']);
    return $tracking;
}

function gawdee_delhivery_download_label(string $waybill): array
{
    if (!gawdee_delhivery_configured() || !preg_match('/^[A-Za-z0-9_-]{5,60}$/', $waybill)) {
        throw new RuntimeException('A valid configured Delhivery waybill is required.');
    }
    $handle = curl_init(gawdee_delhivery_base_url() . '/api/p/packing_slip?wbns=' . rawurlencode($waybill) . '&pdf=true');
    if ($handle === false) {
        throw new RuntimeException('Unable to initialize the Delhivery label request.');
    }
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => ['Authorization: Token ' . gawdee_setting('delhivery_api_token'), 'Accept: application/pdf, application/json'],
    ]);
    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $contentType = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
    $error = curl_error($handle);
    curl_close($handle);
    if ($body === false || $status < 200 || $status >= 300) {
        throw new RuntimeException($body === false ? 'Delhivery label request failed: ' . $error : 'Delhivery could not generate the label.');
    }
    return ['body' => (string) $body, 'content_type' => str_contains(strtolower($contentType), 'pdf') ? 'application/pdf' : $contentType];
}

function gawdee_normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }
    if (strlen($digits) === 10) {
        $digits = '91' . $digits;
    }
    return preg_match('/^[1-9][0-9]{7,14}$/', $digits) ? $digits : '';
}

function gawdee_whatsapp_configured(): bool
{
    return gawdee_setting('whatsapp_cloud_enabled', '0') === '1'
        && gawdee_setting('whatsapp_phone_number_id') !== ''
        && gawdee_setting('whatsapp_access_token') !== '';
}

function gawdee_whatsapp_template_payload(string $phone, string $templateName, array $parameters = [], ?string $language = null, bool $otpButton = false): array
{
    $phone = gawdee_normalize_phone($phone);
    if ($phone === '' || !preg_match('/^[a-z0-9_]{1,512}$/', $templateName)) {
        throw new RuntimeException('The WhatsApp recipient or approved template name is invalid.');
    }
    $components = [];
    if ($parameters) {
        $bodyParameters = array_map(static fn(mixed $value): array => ['type' => 'text', 'text' => mb_substr((string) $value, 0, 1024)], array_values($parameters));
        $components[] = ['type' => 'body', 'parameters' => $bodyParameters];
        if ($otpButton) {
            $components[] = ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => (string) $parameters[0]]]];
        }
    }
    $template = ['name' => $templateName, 'language' => ['code' => $language ?: gawdee_setting('whatsapp_language', 'en_US')]];
    if ($components) {
        $template['components'] = $components;
    }
    return ['messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'template', 'template' => $template];
}

function gawdee_whatsapp_send_template(string $phone, string $templateName, array $parameters = [], ?string $language = null, bool $otpButton = false): array
{
    // Meta WhatsApp Cloud API messages collection: https://www.postman.com/meta/whatsapp-business-platform/documentation/wlk6lh4/whatsapp-cloud-api
    if (!gawdee_whatsapp_configured()) {
        throw new RuntimeException('WhatsApp Cloud API is not configured or enabled.');
    }
    $version = preg_match('/^v[0-9]+\.[0-9]+$/', gawdee_setting('whatsapp_graph_version', 'v23.0'))
        ? gawdee_setting('whatsapp_graph_version', 'v23.0')
        : 'v23.0';
    $payload = gawdee_whatsapp_template_payload($phone, $templateName, $parameters, $language, $otpButton);
    try {
        $response = gawdee_http_request(
            'POST',
            'https://graph.facebook.com/' . $version . '/' . rawurlencode(gawdee_setting('whatsapp_phone_number_id')) . '/messages',
            ['Content-Type: application/json', 'Authorization: Bearer ' . gawdee_setting('whatsapp_access_token')],
            $payload,
            30
        );
        $messageId = (string) ($response['data']['messages'][0]['id'] ?? '');
        if ($messageId === '') {
            throw new RuntimeException('WhatsApp accepted the request but returned no message ID.');
        }
        gawdee_log_integration('whatsapp', 'send_template', 'success', $templateName, $messageId);
        return ['message_id' => $messageId, 'response' => $response['data']];
    } catch (Throwable $error) {
        gawdee_log_integration('whatsapp', 'send_template', 'failed', $error->getMessage(), $templateName);
        throw $error;
    }
}

function gawdee_whatsapp_verify_webhook(string $rawBody, string $signature): bool
{
    // Meta webhook HMAC validation: https://whatsapp.github.io/WhatsApp-Nodejs-SDK/api-reference/webhooks/start/
    $secret = gawdee_setting('whatsapp_app_secret');
    if ($secret === '' || !str_starts_with($signature, 'sha256=')) {
        return false;
    }
    return hash_equals('sha256=' . hash_hmac('sha256', $rawBody, $secret), $signature);
}

function gawdee_record_webhook_event(string $provider, string $eventKey, string $eventType, string $rawBody): bool
{
    $statement = gawdee_db()->prepare('INSERT OR IGNORE INTO webhook_events (provider, event_key, event_type, payload_hash) VALUES (?, ?, ?, ?)');
    $statement->execute([$provider, mb_substr($eventKey, 0, 255), mb_substr($eventType, 0, 120), hash('sha256', $rawBody)]);
    return $statement->rowCount() === 1;
}

function gawdee_complete_webhook_event(string $provider, string $eventKey, string $status = 'processed'): void
{
    gawdee_db()->prepare('UPDATE webhook_events SET status=?, processed_at=CURRENT_TIMESTAMP WHERE provider=? AND event_key=?')
        ->execute([$status, $provider, $eventKey]);
}

function gawdee_order_notification_details(array $order, string $type): array
{
    $tracking = gawdee_order_tracking_reference($order);
    $templates = [
        'order_confirmed' => ['whatsapp_template_order_confirmed', [$order['customer_name'], $order['order_number'], '₹' . number_format((int) $order['total'])]],
        'payment_confirmed' => ['whatsapp_template_payment_confirmed', [$order['customer_name'], $order['order_number'], '₹' . number_format((int) $order['total'])]],
        'order_packed' => ['whatsapp_template_order_packed', [$order['customer_name'], $order['order_number']]],
        'order_shipped' => ['whatsapp_template_order_shipped', [$order['customer_name'], $order['order_number'], $order['courier_name'] ?: 'Gawdee delivery', $tracking ?: 'Tracking will update shortly']],
        'order_delivered' => ['whatsapp_template_order_delivered', [$order['customer_name'], $order['order_number']]],
        'order_cancelled' => ['whatsapp_template_order_cancelled', [$order['customer_name'], $order['order_number']]],
    ];
    if (!isset($templates[$type])) {
        throw new RuntimeException('Unknown order notification type.');
    }
    [$settingKey, $variables] = $templates[$type];
    return ['template' => gawdee_setting($settingKey), 'variables' => $variables];
}

function gawdee_queue_order_notification(int $orderId, string $type): bool
{
    if (gawdee_setting('whatsapp_cloud_enabled', '0') !== '1' || gawdee_setting('whatsapp_order_notifications', '1') !== '1') {
        return false;
    }
    $order = gawdee_order_by_id($orderId);
    if (!$order || gawdee_normalize_phone((string) $order['phone']) === '') {
        return false;
    }
    $details = gawdee_order_notification_details($order, $type);
    if ($details['template'] === '') {
        return false;
    }
    $tracking = gawdee_order_tracking_reference($order);
    $dedupe = implode(':', ['order', $orderId, $type, $tracking ?: 'none']);
    $statement = gawdee_db()->prepare(<<<'SQL'
INSERT OR IGNORE INTO notification_queue
(order_id, user_id, channel, notification_type, recipient, template_name, language, variables_json, dedupe_key)
VALUES (?, ?, 'whatsapp', ?, ?, ?, ?, ?, ?)
SQL);
    $statement->execute([
        $orderId,
        $order['user_id'] ?: null,
        $type,
        gawdee_normalize_phone((string) $order['phone']),
        $details['template'],
        gawdee_setting('whatsapp_language', 'en_US'),
        json_encode($details['variables'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        $dedupe,
    ]);
    return $statement->rowCount() === 1;
}

function gawdee_process_notification_queue(int $limit = 20): array
{
    $limit = min(100, max(1, $limit));
    $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
    if (!gawdee_whatsapp_configured()) {
        return $result;
    }
    $rows = gawdee_db()->query("SELECT * FROM notification_queue WHERE status IN ('queued','retry') AND attempts < 5 AND scheduled_at <= CURRENT_TIMESTAMP ORDER BY id LIMIT {$limit}")->fetchAll();
    foreach ($rows as $row) {
        if ($row['notification_type'] === 'marketing') {
            $consent = false;
            if ($row['user_id']) {
                $check = gawdee_db()->prepare("SELECT whatsapp_marketing_opt_in FROM users WHERE id=? AND whatsapp_opt_out_at IS NULL");
                $check->execute([(int) $row['user_id']]);
                $consent = (int) $check->fetchColumn() === 1;
            }
            if (!$consent || gawdee_setting('whatsapp_marketing_enabled', '0') !== '1') {
                gawdee_db()->prepare("UPDATE notification_queue SET status='cancelled', error_message='No active marketing consent.', updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([(int) $row['id']]);
                $result['skipped']++;
                continue;
            }
        }
        try {
            $variables = json_decode((string) $row['variables_json'], true);
            $sent = gawdee_whatsapp_send_template((string) $row['recipient'], (string) $row['template_name'], is_array($variables) ? $variables : [], (string) $row['language']);
            gawdee_db()->prepare("UPDATE notification_queue SET status='sent', attempts=attempts+1, provider_message_id=?, error_message='', sent_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                ->execute([$sent['message_id'], (int) $row['id']]);
            $result['sent']++;
        } catch (Throwable $error) {
            gawdee_db()->prepare("UPDATE notification_queue SET status=CASE WHEN attempts+1 >= 5 THEN 'failed' ELSE 'retry' END, attempts=attempts+1, error_message=?, scheduled_at=datetime('now', '+' || MIN(60, (attempts+1)*(attempts+1)*5) || ' minutes'), updated_at=CURRENT_TIMESTAMP WHERE id=?")
                ->execute([mb_substr($error->getMessage(), 0, 1000), (int) $row['id']]);
            $result['failed']++;
        }
    }
    return $result;
}

function gawdee_queue_marketing_broadcast(string $templateName, array $parameters = []): int
{
    if (gawdee_setting('whatsapp_marketing_enabled', '0') !== '1') {
        throw new RuntimeException('Enable consent-based WhatsApp marketing before queuing a campaign.');
    }
    if (!preg_match('/^[a-z0-9_]{1,512}$/', $templateName)) {
        throw new RuntimeException('Enter a valid approved WhatsApp marketing template name.');
    }
    $customers = gawdee_db()->query("SELECT id, phone FROM users WHERE role='customer' AND whatsapp_marketing_opt_in=1 AND whatsapp_opt_out_at IS NULL AND phone!='' ORDER BY id")->fetchAll();
    $inserted = 0;
    $campaign = hash('sha256', $templateName . '|' . json_encode($parameters) . '|' . date('Y-m-d-H-i'));
    $statement = gawdee_db()->prepare("INSERT OR IGNORE INTO notification_queue (user_id, channel, notification_type, recipient, template_name, language, variables_json, dedupe_key) VALUES (?, 'whatsapp', 'marketing', ?, ?, ?, ?, ?)");
    foreach ($customers as $customer) {
        $phone = gawdee_normalize_phone((string) $customer['phone']);
        if ($phone === '') {
            continue;
        }
        $statement->execute([(int) $customer['id'], $phone, $templateName, gawdee_setting('whatsapp_language', 'en_US'), json_encode($parameters, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'marketing:' . $campaign . ':' . $customer['id']]);
        $inserted += $statement->rowCount();
    }
    return $inserted;
}

function gawdee_customer_by_identity(string $identity): ?array
{
    $email = strtolower(trim($identity));
    $phone = gawdee_normalize_phone($identity);
    $statement = gawdee_db()->prepare("SELECT * FROM users WHERE role='customer' AND (LOWER(email)=? OR (? != '' AND ('91' || substr(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), -10))=?)) LIMIT 1");
    $statement->execute([$email, $phone, $phone]);
    return $statement->fetch() ?: null;
}

function gawdee_whatsapp_request_otp(string $identity, string $ipAddress = ''): bool
{
    if (!gawdee_whatsapp_configured() || gawdee_setting('whatsapp_otp_enabled', '0') !== '1') {
        throw new RuntimeException('WhatsApp OTP login is not enabled yet.');
    }
    $ipHash = hash_hmac('sha256', $ipAddress ?: 'unknown', gawdee_secret_key());
    $customer = gawdee_customer_by_identity($identity);
    if (!$customer || gawdee_normalize_phone((string) $customer['phone']) === '') {
        return false;
    }
    $phone = gawdee_normalize_phone((string) $customer['phone']);
    $rate = gawdee_db()->prepare("SELECT COUNT(*) FROM customer_otps WHERE (phone=? OR requested_ip_hash=?) AND created_at >= datetime('now','-15 minutes')");
    $rate->execute([$phone, $ipHash]);
    if ((int) $rate->fetchColumn() >= 5) {
        throw new RuntimeException('Too many OTP requests. Please wait 15 minutes and try again.');
    }
    gawdee_db()->prepare("UPDATE customer_otps SET status='expired' WHERE user_id=? AND purpose='login' AND status='pending'")->execute([(int) $customer['id']]);
    $code = (string) random_int(100000, 999999);
    $statement = gawdee_db()->prepare("INSERT INTO customer_otps (user_id, phone, purpose, code_hash, expires_at, requested_ip_hash) VALUES (?, ?, 'login', ?, datetime('now','+10 minutes'), ?)");
    $statement->execute([(int) $customer['id'], $phone, password_hash($code, PASSWORD_DEFAULT), $ipHash]);
    $otpId = (int) gawdee_db()->lastInsertId();
    try {
        gawdee_whatsapp_send_template($phone, gawdee_setting('whatsapp_template_otp', 'gawdee_login_otp'), [$code], gawdee_setting('whatsapp_language', 'en_US'), true);
        return true;
    } catch (Throwable $error) {
        gawdee_db()->prepare("UPDATE customer_otps SET status='failed' WHERE id=?")->execute([$otpId]);
        throw $error;
    }
}

function gawdee_whatsapp_verify_otp(string $identity, string $code): ?array
{
    if (!preg_match('/^[0-9]{6}$/', $code)) {
        return null;
    }
    $customer = gawdee_customer_by_identity($identity);
    if (!$customer) {
        return null;
    }
    $statement = gawdee_db()->prepare("SELECT * FROM customer_otps WHERE user_id=? AND purpose='login' AND status='pending' AND expires_at >= CURRENT_TIMESTAMP ORDER BY id DESC LIMIT 1");
    $statement->execute([(int) $customer['id']]);
    $otp = $statement->fetch();
    if (!$otp || (int) $otp['attempts'] >= 5) {
        return null;
    }
    if (!password_verify($code, (string) $otp['code_hash'])) {
        gawdee_db()->prepare("UPDATE customer_otps SET attempts=attempts+1, status=CASE WHEN attempts+1 >= 5 THEN 'expired' ELSE status END WHERE id=?")->execute([(int) $otp['id']]);
        return null;
    }
    gawdee_db()->prepare("UPDATE customer_otps SET status='consumed', consumed_at=CURRENT_TIMESTAMP WHERE id=?")->execute([(int) $otp['id']]);
    return $customer;
}
