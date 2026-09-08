<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$orderNumber = trim((string) ($_GET['order'] ?? ''));
$customer = gawdee_require_customer('account-order.php?order=' . rawurlencode($orderNumber));
$order = $orderNumber !== '' ? gawdee_customer_order((int) $customer['id'], $orderNumber) : null;
if (!$order) {
    http_response_code(404);
}

$pageTitle = $order ? 'Order ' . $order['order_number'] . ' | Gawdee' : 'Order not found | Gawdee';
$pageDescription = 'Gawdee order details and tracked delivery progress.';
$bodyClass = 'customer-account-page account-order-page';
require __DIR__ . '/includes/header.php';

$steps = [
    'pending' => ['Order received', 'We received your order and payment choice.', 'ph-receipt'],
    'processing' => ['Confirmed', 'Your order is confirmed and being prepared.', 'ph-check-circle'],
    'packed' => ['Packed with care', 'Your products are packed for dispatch.', 'ph-package'],
    'shipped' => ['On the way', 'The shipment has left with the courier.', 'ph-truck'],
    'delivered' => ['Delivered', 'Your Gawdee order has reached its destination.', 'ph-house-line'],
];
$positions = array_keys($steps);
$currentIndex = $order ? (array_search($order['status'], $positions, true) ?: 0) : 0;
$trackingReference = $order ? gawdee_order_tracking_reference($order) : '';
$trackingUrl = $order ? gawdee_order_tracking_url($order) : '';
?>
<section class="order-detail-shell section">
    <div class="container">
        <a class="order-detail-back" href="account.php#orders"><i class="ph ph-arrow-left"></i> Back to my orders</a>
        <?php if (!$order): ?><div class="account-empty"><i class="ph ph-magnifying-glass"></i><span class="eyebrow">Order not found</span><h1>This order is not available in your account.</h1><p>For privacy, only the signed-in owner can view an order.</p><a class="button button--primary" href="account.php">Go to my account</a></div><?php else: ?>
            <div class="order-detail-heading"><div><span class="eyebrow">Order tracking</span><h1><?= htmlspecialchars($order['order_number']) ?></h1><p>Placed <?= htmlspecialchars(date('j F Y \a\t g:i a', strtotime((string) $order['created_at']))) ?></p></div><div><span class="account-status account-status--<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $order['status']))) ?></span><?php if ($trackingUrl): ?><a class="button button--primary" href="<?= htmlspecialchars($trackingUrl) ?>" target="_blank" rel="noopener">Open shipment tracking <i class="ph ph-arrow-up-right"></i></a><?php endif; ?></div></div>

            <?php if (in_array($order['status'], ['cancelled', 'refunded'], true)): ?><div class="order-cancelled"><i class="ph ph-info"></i><p><strong><?= htmlspecialchars(ucfirst($order['status'])) ?> order</strong>This order is no longer moving through fulfilment. Contact support if you need assistance.</p></div><?php endif; ?>

            <div class="order-detail-grid">
                <main>
                    <section class="order-detail-card order-journey"><div class="order-detail-card__head"><i class="ph ph-map-trifold"></i><div><h2>Delivery journey</h2><p>Status updates from Gawdee fulfilment</p></div></div><div class="order-journey__steps">
                        <?php foreach ($steps as $status => [$title, $description, $icon]): $index = array_search($status, $positions, true); $complete = $index <= $currentIndex && !in_array($order['status'], ['cancelled','refunded'], true); ?>
                            <article class="<?= $complete ? 'is-complete' : '' ?> <?= $status === $order['status'] ? 'is-current' : '' ?>"><span><i class="ph <?= $icon ?>"></i></span><div><strong><?= htmlspecialchars($title) ?></strong><p><?= htmlspecialchars($description) ?></p><?php if ($status === 'shipped' && $trackingReference): ?><small><?= htmlspecialchars($order['courier_name'] ?: 'Courier') ?> reference: <?= htmlspecialchars($trackingReference) ?></small><?php endif; ?></div><i class="ph <?= $complete ? 'ph-check-circle' : 'ph-circle' ?>"></i></article>
                        <?php endforeach; ?>
                    </div></section>

                    <?php if ($order['events']): ?><section class="order-detail-card"><div class="order-detail-card__head"><i class="ph ph-clock-counter-clockwise"></i><div><h2>Order updates</h2><p>Recorded activity for this order</p></div></div><div class="order-event-list"><?php foreach (array_reverse($order['events']) as $event): ?><article><span></span><div><strong><?= htmlspecialchars($event['title']) ?></strong><p><?= htmlspecialchars($event['description']) ?></p></div><time><?= htmlspecialchars(date('j M, g:i a', strtotime((string) $event['created_at']))) ?></time></article><?php endforeach; ?></div></section><?php endif; ?>

                    <section class="order-detail-card"><div class="order-detail-card__head"><i class="ph ph-shopping-bag-open"></i><div><h2>Items in this order</h2><p><?= count($order['items']) ?> product line<?= count($order['items']) === 1 ? '' : 's' ?></p></div></div><div class="order-item-list"><?php foreach ($order['items'] as $item): $itemProduct = gawdee_product_by_id((string) $item['product_id']); ?><article><img src="<?= htmlspecialchars($item['image']) ?>" alt=""><div><strong><?= htmlspecialchars($item['product_name']) ?></strong><span>Quantity <?= (int) $item['quantity'] ?> · <?= money((int) $item['unit_price']) ?> each</span></div><b><?= money((int) $item['quantity'] * (int) $item['unit_price']) ?></b><?php if ($itemProduct): ?><a href="product.php?slug=<?= rawurlencode((string) $itemProduct['slug']) ?>" aria-label="View product"><i class="ph ph-arrow-up-right"></i></a><?php endif; ?></article><?php endforeach; ?></div></section>
                </main>

                <aside>
                    <section class="order-detail-card order-summary-card"><h2>Order summary</h2><p><span>Subtotal</span><strong><?= money((int) $order['subtotal']) ?></strong></p><?php if ((int) $order['discount'] > 0): ?><p><span>Offer <?= htmlspecialchars($order['coupon_code']) ?></span><strong>−<?= money((int) $order['discount']) ?></strong></p><?php endif; ?><p><span>Delivery</span><strong><?= (int) $order['shipping'] === 0 ? 'Free' : money((int) $order['shipping']) ?></strong></p><p class="order-summary-card__total"><span>Total</span><strong><?= money((int) $order['total']) ?></strong></p><div><i class="ph ph-credit-card"></i><span><strong><?= htmlspecialchars($order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Razorpay') ?></strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $order['payment_status']))) ?></span></div></section>
                    <section class="order-detail-card order-address-card"><div class="order-detail-card__head"><i class="ph ph-map-pin"></i><div><h2>Delivery address</h2><p>Destination for this order</p></div></div><address><strong><?= htmlspecialchars($order['customer_name']) ?></strong><?= htmlspecialchars($order['address1']) ?><br><?php if ($order['address2']): ?><?= htmlspecialchars($order['address2']) ?><br><?php endif; ?><?= htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['pincode']) ?><br><?= htmlspecialchars($order['phone']) ?></address></section>
                    <section class="order-help-card"><i class="ph ph-headset"></i><h3>Need help with this order?</h3><p>Our support team can help with fulfilment, delivery or payment questions.</p><a href="mailto:<?= htmlspecialchars(gawdee_setting('store_email','info@gawdee.com')) ?>?subject=Help with order <?= rawurlencode((string) $order['order_number']) ?>">Contact order support <i class="ph ph-arrow-right"></i></a></section>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
