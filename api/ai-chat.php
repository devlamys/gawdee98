<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/integrations.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gawdee_json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

try {
    $payload = gawdee_request_json();
    gawdee_verify_csrf($payload['csrf_token'] ?? null);
    if (gawdee_setting('ai_chat_enabled', '1') !== '1') {
        throw new RuntimeException('The Gawdee AI assistant is currently offline.');
    }
    $message = trim(mb_substr((string) ($payload['message'] ?? ''), 0, 700));
    if (mb_strlen($message) < 2) {
        throw new RuntimeException('Type a question for the Gawdee assistant.');
    }
    $attempts = array_values(array_filter($_SESSION['ai_chat_attempts'] ?? [], static fn($timestamp): bool => (int) $timestamp > time() - 300));
    if (count($attempts) >= 12) {
        throw new RuntimeException('Please wait a few minutes before asking another question.');
    }
    $attempts[] = time();
    $_SESSION['ai_chat_attempts'] = $attempts;

    $catalogue = implode("\n", array_map(static fn(array $product): string => '- ' . $product['full_name'] . ' — ₹' . $product['price'] . ', ' . $product['description'], gawdee_products()));
    $instructions = "You are the Gawdee shopping assistant. Be warm, concise and practical. Answer only from the approved catalogue and store information below. Never diagnose, prescribe, or promise health outcomes. For medical, allergy, pregnancy or disease questions, recommend consulting a qualified professional and checking the product label. If unsure, say so.\n\nApproved catalogue:\n{$catalogue}\n\nStore: Free shipping above ₹" . gawdee_setting('free_shipping_threshold', '999') . '. Support: ' . gawdee_setting('store_email', 'info@gawdee.com') . '.';
    $reply = gawdee_ai_generate($instructions, $message, 500);
    gawdee_json_response(['ok' => true, 'reply' => $reply, 'provider' => gawdee_setting('ai_provider', 'groq')]);
} catch (Throwable $exception) {
    gawdee_json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}
