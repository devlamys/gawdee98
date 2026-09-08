<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';

gawdee_require_admin();
$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'payment_status' => trim((string) ($_GET['payment_status'] ?? '')),
    'fulfillment_mode' => trim((string) ($_GET['fulfillment_mode'] ?? '')),
];
$orders = gawdee_admin_orders($filters, 10000);

function admin_csv_value(mixed $value): string
{
    $value = (string) $value;
    return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="gawdee-orders-' . date('Y-m-d-His') . '.csv"');
header('X-Content-Type-Options: nosniff');
$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Order', 'Created', 'Source', 'Customer', 'Email', 'Phone', 'City', 'State', 'Pincode', 'Payment method', 'Payment status', 'Order status', 'Fulfilment', 'Courier', 'Tracking', 'Subtotal', 'Discount', 'Shipping', 'Total', 'Coupon'], ',', '"', '', "\r\n");
foreach ($orders as $order) {
    fputcsv($output, array_map('admin_csv_value', [
        $order['order_number'], $order['created_at'], $order['source'], $order['customer_name'], $order['email'], $order['phone'],
        $order['city'], $order['state'], $order['pincode'], $order['payment_method'], $order['payment_status'], $order['status'],
        $order['fulfillment_mode'], $order['courier_name'], gawdee_order_tracking_reference($order), $order['subtotal'], $order['discount'],
        $order['shipping'], $order['total'], $order['coupon_code'],
    ]), ',', '"', '', "\r\n");
}
fclose($output);
exit;
