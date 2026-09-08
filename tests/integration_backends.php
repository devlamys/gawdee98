<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/integrations.php';

$failures = [];
$check = static function (bool $condition, string $label) use (&$failures): void {
    echo ($condition ? 'PASS' : 'FAIL') . '  ' . $label . PHP_EOL;
    if (!$condition) {
        $failures[] = $label;
    }
};

$tables = array_column(gawdee_db()->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(), 'name');
$check(in_array('customer_otps', $tables, true) && in_array('notification_queue', $tables, true) && in_array('webhook_events', $tables, true), 'integration persistence schema');
$orderColumns = array_column(gawdee_db()->query('PRAGMA table_info(orders)')->fetchAll(), 'name');
$check(in_array('delhivery_waybill', $orderColumns, true) && in_array('delhivery_last_status', $orderColumns, true), 'Delhivery order fields');

$check(gawdee_normalize_phone('98765 43210') === '919876543210', 'Indian WhatsApp phone normalization');
$check(gawdee_normalize_phone('+91 98765-43210') === '919876543210', 'international WhatsApp phone normalization');
$check(gawdee_normalize_phone('123') === '', 'invalid WhatsApp phone rejection');

$message = gawdee_whatsapp_template_payload('9876543210', 'gawdee_order_confirmed', ['Customer', 'GD123', '₹999']);
$check(($message['to'] ?? '') === '919876543210' && count($message['template']['components'][0]['parameters'] ?? []) === 3, 'WhatsApp approved-template payload');
$otp = gawdee_whatsapp_template_payload('9876543210', 'gawdee_login_otp', ['123456'], 'en_US', true);
$check(($otp['template']['components'][1]['sub_type'] ?? '') === 'url', 'WhatsApp OTP copy-code component');

$order = [
    'id' => 1, 'order_number' => 'GDTEST', 'customer_name' => 'Integration QA', 'phone' => '9876543210',
    'address1' => '10 Test Street', 'address2' => '', 'city' => 'New Delhi', 'state' => 'Delhi', 'pincode' => '110001',
    'payment_method' => 'cod', 'total' => 1099, 'created_at' => '2026-09-08 12:00:00',
];
$shipment = gawdee_delhivery_build_payload($order, [['product_id' => 'ghee', 'product_name' => 'A2 Ghee', 'quantity' => 2]]);
$check(($shipment['shipments'][0]['payment_mode'] ?? '') === 'COD' && ($shipment['shipments'][0]['cod_amount'] ?? 0) === 1099, 'Delhivery COD manifestation payload');
$check(($shipment['shipments'][0]['quantity'] ?? 0) === 2 && ($shipment['shipments'][0]['phone'] ?? '') === '919876543210', 'Delhivery quantity and consignee mapping');
$check(gawdee_delhivery_map_order_status('In Transit') === 'shipped' && gawdee_delhivery_map_order_status('Delivered') === 'delivered', 'Delhivery status mapping');

$eventKey = 'qa-' . bin2hex(random_bytes(8));
$check(gawdee_record_webhook_event('qa', $eventKey, 'test', '{}'), 'webhook first-delivery acceptance');
$check(!gawdee_record_webhook_event('qa', $eventKey, 'test', '{}'), 'webhook duplicate rejection');
gawdee_db()->prepare('DELETE FROM webhook_events WHERE provider=? AND event_key=?')->execute(['qa', $eventKey]);

exit($failures ? 1 : 0);
