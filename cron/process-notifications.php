<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/integrations.php';

$expected = gawdee_setting('notification_cron_token');
$provided = (string) ($_GET['token'] ?? ($_SERVER['HTTP_X_CRON_TOKEN'] ?? ''));
if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
    gawdee_json_response(['ok' => false, 'message' => 'Unauthorized.'], 403);
}

try {
    $result = gawdee_process_notification_queue(50);
    gawdee_json_response(['ok' => true] + $result);
} catch (Throwable $error) {
    gawdee_log_integration('whatsapp', 'process_queue', 'failed', $error->getMessage());
    gawdee_json_response(['ok' => false, 'message' => 'Notification processing failed.'], 500);
}
