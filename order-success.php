<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$orderNumber = trim((string) ($_GET['order'] ?? ''));
$signedInCustomer = gawdee_customer();
$authorized = $orderNumber !== '' && hash_equals((string) ($_SESSION['last_order_number'] ?? ''), $orderNumber);
$order = null;
if ($authorized || $signedInCustomer) {
    $statement = $signedInCustomer
        ? gawdee_db()->prepare('SELECT * FROM orders WHERE order_number=? AND user_id=?')
        : gawdee_db()->prepare('SELECT * FROM orders WHERE order_number=?');
    $statement->execute($signedInCustomer ? [$orderNumber, (int) $signedInCustomer['id']] : [$orderNumber]);
    $order = $statement->fetch() ?: null;
}

$pageTitle = 'Order received | Gawdee';
$bodyClass = 'order-success-page';
require __DIR__ . '/includes/header.php';
?>
<section class="order-success-shell">
    <div class="order-success-card">
        <span class="order-success-icon"><i class="ph ph-check"></i></span>
        <?php if ($order): ?>
            <span class="eyebrow">Order confirmed</span>
            <h1>Thank you,<br><em><?= htmlspecialchars(explode(' ', (string) $order['customer_name'])[0]) ?>.</em></h1>
            <p>Your order <strong><?= htmlspecialchars($order['order_number']) ?></strong> is now in our care. A confirmation will be sent to <?= htmlspecialchars($order['email']) ?>.</p>
            <div class="order-success-details"><div><span>Payment</span><strong><?= htmlspecialchars($order['payment_method'] === 'cod' ? 'Cash on delivery' : ucfirst($order['payment_status'])) ?></strong></div><div><span>Order total</span><strong><?= money((int) $order['total']) ?></strong></div><div><span>Status</span><strong><?= htmlspecialchars(ucfirst($order['status'])) ?></strong></div></div>
        <?php else: ?>
            <span class="eyebrow">Order update</span><h1>Your order is<br><em>in good hands.</em></h1><p>For privacy, order details are visible only to the customer who placed the order.</p>
        <?php endif; ?>
        <div class="order-success-actions">
            <?php if ($order && $signedInCustomer): ?><a class="button button--primary" href="account-order.php?order=<?= rawurlencode((string) $order['order_number']) ?>">View order tracking <i class="ph ph-map-pin"></i></a><?php else: ?><a class="button button--primary" href="index.php#shop">Continue shopping <i class="ph ph-arrow-right"></i></a><?php endif; ?>
            <a class="button button--secondary" href="<?= $signedInCustomer ? 'account.php' : 'register.php' ?>"><?= $signedInCustomer ? 'My account' : 'Create tracking account' ?></a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
