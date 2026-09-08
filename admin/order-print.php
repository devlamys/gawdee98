<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';

gawdee_require_admin();
$orderId = (int) ($_GET['order'] ?? 0);
$order = gawdee_order_by_id($orderId);
if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}
$items = gawdee_order_items($orderId);
$type = ($_GET['type'] ?? '') === 'packing' ? 'packing' : 'invoice';
$isPacking = $type === 'packing';
$documentTitle = $isPacking ? 'Packing slip' : 'Order invoice';
$statusLabels = gawdee_order_status_labels();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($documentTitle . ' ' . $order['order_number']) ?></title>
    <style>
        :root{font-family:Arial,sans-serif;color:#17251e}*{box-sizing:border-box}body{margin:0;background:#edf2ee}.print-actions{position:sticky;top:0;display:flex;justify-content:center;gap:10px;padding:14px;background:#073c2b}.print-actions button,.print-actions a{display:inline-flex;align-items:center;min-height:42px;padding:0 18px;border:0;border-radius:9px;color:#073c2b;background:#fff;font-weight:700;text-decoration:none;cursor:pointer}.document{width:210mm;min-height:297mm;margin:24px auto;padding:20mm;background:#fff;box-shadow:0 15px 45px #16342720}.document header{display:flex;justify-content:space-between;gap:40px;padding-bottom:24px;border-bottom:2px solid #0b7148}.brand{font-size:30px;font-weight:900;letter-spacing:-1.5px;color:#086b43}.brand small{display:block;margin-top:6px;color:#7c8881;font-size:11px;font-weight:500;letter-spacing:1.2px;text-transform:uppercase}.document-meta{text-align:right}.document-meta h1{margin:0 0 8px;font-size:22px}.document-meta p{margin:3px 0;color:#66736c;font-size:12px}.address-grid{display:grid;grid-template-columns:1fr 1fr;gap:35px;padding:28px 0}.address-grid h2{margin:0 0 10px;color:#718078;font-size:11px;letter-spacing:1px;text-transform:uppercase}.address-grid p{margin:0;font-size:13px;line-height:1.65}.address-grid strong{font-size:15px}.summary-strip{display:grid;grid-template-columns:repeat(4,1fr);margin-bottom:24px;border:1px solid #dfe7e1;border-radius:12px}.summary-strip div{padding:13px;border-right:1px solid #dfe7e1}.summary-strip div:last-child{border:0}.summary-strip span,.summary-strip strong{display:block}.summary-strip span{margin-bottom:4px;color:#77847c;font-size:10px;text-transform:uppercase}.summary-strip strong{font-size:12px}table{width:100%;border-collapse:collapse}th{padding:11px 10px;color:#718078;background:#f3f6f4;font-size:10px;letter-spacing:.7px;text-align:left;text-transform:uppercase}td{padding:14px 10px;border-bottom:1px solid #e5eae6;font-size:12px}th:nth-child(n+2),td:nth-child(n+2){text-align:right}.product strong,.product span{display:block}.product span{margin-top:3px;color:#829087;font-size:10px}.totals{width:285px;margin:24px 0 0 auto}.totals p{display:flex;justify-content:space-between;margin:0;padding:7px 0;color:#6f7c75;font-size:12px}.totals .grand{margin-top:6px;padding-top:13px;border-top:2px solid #173a29;color:#173a29;font-size:15px;font-weight:800}.notes{margin-top:35px;padding:16px;border-radius:10px;background:#f4f7f5}.notes h2{margin:0 0 6px;font-size:12px}.notes p{margin:0;color:#5d6b63;font-size:11px;line-height:1.6}.document footer{display:flex;justify-content:space-between;margin-top:55px;padding-top:15px;border-top:1px solid #dfe6e1;color:#7a867f;font-size:10px}@media print{body{background:#fff}.print-actions{display:none}.document{width:auto;min-height:auto;margin:0;padding:12mm;box-shadow:none}}@media(max-width:800px){.document{width:100%;min-height:0;margin:0;padding:25px}.address-grid{grid-template-columns:1fr}.summary-strip{grid-template-columns:1fr 1fr}.summary-strip div:nth-child(2){border-right:0}.summary-strip div:nth-child(-n+2){border-bottom:1px solid #dfe7e1}}
    </style>
</head>
<body>
<div class="print-actions"><button type="button" onclick="window.print()">Print <?= htmlspecialchars(strtolower($documentTitle)) ?></button><a href="index.php?view=orders&order=<?= $orderId ?>">Back to order</a></div>
<main class="document">
    <header><div class="brand"><?= htmlspecialchars(gawdee_setting('store_name', 'Gawdee')) ?><small>Pure by nature</small></div><div class="document-meta"><h1><?= htmlspecialchars($documentTitle) ?></h1><p><strong><?= htmlspecialchars($order['order_number']) ?></strong></p><p><?= htmlspecialchars(date('j F Y, g:i a', strtotime((string) $order['created_at']))) ?></p></div></header>
    <section class="address-grid"><div><h2>Ship to</h2><p><strong><?= htmlspecialchars($order['customer_name']) ?></strong><br><?= htmlspecialchars($order['address1']) ?><?php if ($order['address2']): ?><br><?= htmlspecialchars($order['address2']) ?><?php endif; ?><br><?= htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['pincode']) ?><br><?= htmlspecialchars($order['phone']) ?><br><?= htmlspecialchars($order['email']) ?></p></div><div><h2>From</h2><p><strong><?= htmlspecialchars(gawdee_setting('store_name', 'Gawdee')) ?></strong><br><?= htmlspecialchars(gawdee_setting('store_email', '')) ?><br><?= htmlspecialchars(gawdee_setting('store_phone', '')) ?></p></div></section>
    <section class="summary-strip"><div><span>Order status</span><strong><?= htmlspecialchars($statusLabels[$order['status']] ?? ucfirst($order['status'])) ?></strong></div><div><span>Payment</span><strong><?= htmlspecialchars(str_replace('_', ' ', ucfirst($order['payment_status']))) ?></strong></div><div><span>Delivery mode</span><strong><?= htmlspecialchars(ucfirst($order['fulfillment_mode'])) ?></strong></div><div><span>Package items</span><strong><?= array_sum(array_map(static fn (array $item): int => (int) $item['quantity'], $items)) ?> units</strong></div></section>
    <table><thead><tr><th>Product</th><th>Quantity</th><?php if (!$isPacking): ?><th>Unit price</th><th>Total</th><?php endif; ?></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td class="product"><strong><?= htmlspecialchars($item['product_name']) ?></strong><span><?= htmlspecialchars($item['product_id']) ?></span></td><td><?= (int) $item['quantity'] ?></td><?php if (!$isPacking): ?><td>₹<?= number_format((int) $item['unit_price']) ?></td><td>₹<?= number_format((int) $item['unit_price'] * (int) $item['quantity']) ?></td><?php endif; ?></tr><?php endforeach; ?></tbody></table>
    <?php if (!$isPacking): ?><div class="totals"><p><span>Subtotal</span><strong>₹<?= number_format((int) $order['subtotal']) ?></strong></p><?php if ((int) $order['discount'] > 0): ?><p><span>Discount <?= htmlspecialchars($order['coupon_code']) ?></span><strong>−₹<?= number_format((int) $order['discount']) ?></strong></p><?php endif; ?><p><span>Delivery</span><strong><?= (int) $order['shipping'] === 0 ? 'Free' : '₹' . number_format((int) $order['shipping']) ?></strong></p><p class="grand"><span>Total</span><strong>₹<?= number_format((int) $order['total']) ?></strong></p></div><?php endif; ?>
    <?php if ($order['notes']): ?><section class="notes"><h2>Customer note</h2><p><?= nl2br(htmlspecialchars($order['notes'])) ?></p></section><?php endif; ?>
    <footer><span>Generated securely from Gawdee Admin</span><span><?= htmlspecialchars($order['order_number']) ?></span></footer>
</main>
</body>
</html>
