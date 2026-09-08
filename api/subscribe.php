<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/platform.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gawdee_json_response(['ok' => false], 405);
}
try {
    $payload = gawdee_request_json();
    gawdee_verify_csrf($payload['csrf_token'] ?? null);
    $email = strtolower(trim((string) ($payload['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Enter a valid email address.');
    }
    gawdee_db()->prepare('INSERT OR IGNORE INTO subscribers (email) VALUES (?)')->execute([$email]);
    gawdee_json_response(['ok' => true, 'message' => 'Thank you! Your wellness updates are on the way.']);
} catch (Throwable $exception) {
    gawdee_json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}
