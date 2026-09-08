<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/integrations.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = (string) ($_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '');
    $token = (string) ($_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '');
    $challenge = (string) ($_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '');
    if ($mode === 'subscribe' && $challenge !== '' && gawdee_setting('whatsapp_verify_token') !== '' && hash_equals(gawdee_setting('whatsapp_verify_token'), $token)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $challenge;
        exit;
    }
    http_response_code(403);
    echo 'Verification failed.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gawdee_json_response(['ok' => false], 405);
}

$raw = (string) file_get_contents('php://input');
$signature = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
if (!gawdee_whatsapp_verify_webhook($raw, $signature)) {
    gawdee_log_integration('whatsapp', 'webhook', 'failed', 'Invalid Meta webhook signature.');
    gawdee_json_response(['ok' => false, 'message' => 'Invalid signature.'], 400);
}

$event = json_decode($raw, true);
if (!is_array($event)) {
    gawdee_json_response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
}
$eventKey = hash('sha256', $raw);
if (!gawdee_record_webhook_event('whatsapp', $eventKey, 'messages', $raw)) {
    gawdee_json_response(['ok' => true, 'duplicate' => true]);
}

try {
    foreach (($event['entry'] ?? []) as $entry) {
        foreach (($entry['changes'] ?? []) as $change) {
            $value = $change['value'] ?? [];
            foreach (($value['statuses'] ?? []) as $status) {
                $messageId = trim((string) ($status['id'] ?? ''));
                $state = strtolower(trim((string) ($status['status'] ?? '')));
                if ($messageId === '' || !in_array($state, ['sent', 'delivered', 'read', 'failed'], true)) {
                    continue;
                }
                $errorMessage = '';
                if ($state === 'failed') {
                    $errorMessage = (string) ($status['errors'][0]['title'] ?? $status['errors'][0]['message'] ?? 'WhatsApp reported delivery failure.');
                }
                gawdee_db()->prepare('UPDATE notification_queue SET status=?, error_message=?, updated_at=CURRENT_TIMESTAMP WHERE provider_message_id=?')
                    ->execute([$state, mb_substr($errorMessage, 0, 1000), $messageId]);
            }
            foreach (($value['messages'] ?? []) as $message) {
                $text = strtolower(trim((string) ($message['text']['body'] ?? $message['button']['text'] ?? '')));
                if (!in_array($text, ['stop', 'unsubscribe', 'cancel', 'opt out'], true)) {
                    continue;
                }
                $phone = gawdee_normalize_phone((string) ($message['from'] ?? ''));
                if ($phone !== '') {
                    gawdee_db()->prepare("UPDATE users SET whatsapp_marketing_opt_in=0, whatsapp_opt_out_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE role='customer' AND ('91' || substr(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), -10))=?")
                        ->execute([$phone]);
                }
            }
        }
    }
    gawdee_complete_webhook_event('whatsapp', $eventKey);
    gawdee_log_integration('whatsapp', 'webhook', 'success', 'Delivery receipts processed.', $eventKey);
    gawdee_json_response(['ok' => true]);
} catch (Throwable $error) {
    gawdee_db()->prepare('DELETE FROM webhook_events WHERE provider=? AND event_key=?')->execute(['whatsapp', $eventKey]);
    gawdee_log_integration('whatsapp', 'webhook', 'failed', $error->getMessage(), $eventKey);
    gawdee_json_response(['ok' => false, 'message' => 'Webhook processing will be retried.'], 500);
}
