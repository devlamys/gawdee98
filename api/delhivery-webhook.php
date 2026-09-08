<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/integrations.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gawdee_json_response(['ok' => false], 405);
}

$secret = gawdee_setting('delhivery_webhook_secret');
$provided = trim((string) ($_GET['token'] ?? ''));
if ($provided === '' && preg_match('/^Bearer\s+(.+)$/i', (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''), $match)) {
    $provided = trim($match[1]);
}
if ($secret === '' || $provided === '' || !hash_equals($secret, $provided)) {
    gawdee_json_response(['ok' => false, 'message' => 'Invalid webhook token.'], 403);
}

$raw = (string) file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    gawdee_json_response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
}
$shipment = is_array($payload['Shipment'] ?? null) ? $payload['Shipment'] : $payload;
$waybill = trim((string) ($shipment['AWB'] ?? $shipment['Waybill'] ?? $shipment['waybill'] ?? $payload['waybill'] ?? ''));
$statusBlock = is_array($shipment['Status'] ?? null) ? $shipment['Status'] : [];
$status = trim((string) ($statusBlock['Status'] ?? $shipment['status'] ?? $payload['status'] ?? ''));
$eventKey = $waybill . ':' . hash('sha256', $raw);
if ($waybill === '' || !gawdee_record_webhook_event('delhivery', $eventKey, $status, $raw)) {
    gawdee_json_response(['ok' => true, 'duplicate' => $waybill !== '']);
}

try {
    $statement = gawdee_db()->prepare('SELECT * FROM orders WHERE delhivery_waybill=? LIMIT 1');
    $statement->execute([$waybill]);
    $order = $statement->fetch();
    if ($order) {
        gawdee_db()->prepare('UPDATE orders SET delhivery_last_status=?, delhivery_last_sync_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute([$status, (int) $order['id']]);
        $mapped = gawdee_delhivery_map_order_status($status);
        if ($mapped !== null && $mapped !== $order['status'] && in_array($mapped, gawdee_order_allowed_transitions($order), true)) {
            gawdee_update_order_status((int) $order['id'], $mapped, 'Updated by Delhivery: ' . $status . '.');
        }
    }
    gawdee_process_notification_queue(10);
    gawdee_complete_webhook_event('delhivery', $eventKey);
    gawdee_log_integration('delhivery', 'webhook', 'success', $status, $waybill);
    gawdee_json_response(['ok' => true]);
} catch (Throwable $error) {
    gawdee_db()->prepare('DELETE FROM webhook_events WHERE provider=? AND event_key=?')->execute(['delhivery', $eventKey]);
    gawdee_log_integration('delhivery', 'webhook', 'failed', $error->getMessage(), $waybill);
    gawdee_json_response(['ok' => false, 'message' => 'Webhook processing will be retried.'], 500);
}
