<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/integrations.php';
gawdee_require_admin();

$order = gawdee_order_by_id((int) ($_GET['order'] ?? 0));
if (!$order || trim((string) ($order['delhivery_waybill'] ?? '')) === '') {
    http_response_code(404);
    exit('Delhivery label not found.');
}

try {
    $label = gawdee_delhivery_download_label((string) $order['delhivery_waybill']);
    header('Content-Type: ' . ($label['content_type'] ?: 'application/pdf'));
    header('Content-Disposition: inline; filename="Gawdee-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $order['order_number']) . '-label.pdf"');
    header('Cache-Control: private, no-store');
    echo $label['body'];
} catch (Throwable $error) {
    gawdee_log_integration('delhivery', 'download_label', 'failed', $error->getMessage(), (string) $order['delhivery_waybill']);
    http_response_code(502);
    echo 'Unable to download the Delhivery label: ' . htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');
}
