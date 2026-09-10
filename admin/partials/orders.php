<?php

$orderFilters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'payment_status' => trim((string) ($_GET['payment_status'] ?? '')),
    'fulfillment_mode' => trim((string) ($_GET['fulfillment_mode'] ?? '')),
];
$orders = gawdee_admin_orders($orderFilters);
$db = gawdee_db();
$orderMetrics = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql')
    ? $db->query(<<<'SQL'
SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN DATE(created_at)=CURDATE() THEN 1 ELSE 0 END) AS today,
    SUM(CASE WHEN status IN ('pending','on_hold') OR payment_status IN ('initializing','failed') THEN 1 ELSE 0 END) AS attention,
    SUM(CASE WHEN status IN ('processing','packed') THEN 1 ELSE 0 END) AS fulfillment,
    SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS paid_revenue
FROM orders
SQL)->fetch() ?: []
    : $db->query(<<<'SQL'
SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN date(created_at)=date('now') THEN 1 ELSE 0 END) AS today,
    SUM(CASE WHEN status IN ('pending','on_hold') OR payment_status IN ('initializing','failed') THEN 1 ELSE 0 END) AS attention,
    SUM(CASE WHEN status IN ('processing','packed') THEN 1 ELSE 0 END) AS fulfillment,
    SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS paid_revenue
FROM orders
SQL)->fetch() ?: [];
$detailOrder = null;
$detailItems = [];
$detailEvents = [];
$detailId = (int) ($_GET['order'] ?? 0);
if ($detailId > 0) {
    $detailOrder = gawdee_order_by_id($detailId);
    if ($detailOrder) {
        $detailItems = gawdee_order_items($detailId);
        $detailEvents = gawdee_order_events($detailId);
    }
}
$statusLabels = gawdee_order_status_labels();
$dtdcReady = gawdee_dtdc_configured();
$delhiveryReady = gawdee_delhivery_configured();
$createMode = isset($_GET['new']);
$adminOrderToken = (string) ($_SESSION['admin_order_token'] ?? '');
if ($adminOrderToken === '') {
    $adminOrderToken = bin2hex(random_bytes(18));
    $_SESSION['admin_order_token'] = $adminOrderToken;
}
$orderProducts = array_values(array_filter(gawdee_products(true), static fn (array $product): bool => (int) $product['stock'] > 0 && (int) $product['is_active'] === 1));
?>

<div class="admin-section-title orders-heading">
    <div><h2>Orders & fulfilment</h2><p>Every checkout is received here, whether a courier integration is online or offline.</p></div>
    <div class="admin-actions"><a class="admin-button admin-button--ghost" href="export-orders.php?<?= htmlspecialchars(http_build_query($orderFilters)) ?>"><i class="ph ph-download-simple"></i> Export CSV</a><a class="admin-button <?= $createMode ? 'admin-button--secondary' : 'admin-button--primary' ?>" href="?view=orders<?= $createMode ? '' : '&new=1' ?>"><i class="ph <?= $createMode ? 'ph-x' : 'ph-plus' ?>"></i> <?= $createMode ? 'Close' : 'Create order' ?></a></div>
</div>

<?php if (!$delhiveryReady && !$dtdcReady): ?>
    <div class="fulfilment-mode-banner"><span><i class="ph ph-package"></i></span><div><strong>Manual fulfilment is active</strong><p>Delhivery and DTDC are offline or incomplete. Orders continue to arrive normally; pack and dispatch them manually here.</p></div><a href="?view=integrations">Configure courier <i class="ph ph-arrow-right"></i></a></div>
<?php endif; ?>

<?php if ($createMode): ?>
    <section class="admin-card order-create-card" data-order-builder data-shipping="<?= (int) gawdee_setting('shipping_fee', '99') ?>" data-free-shipping="<?= (int) gawdee_setting('free_shipping_threshold', '999') ?>" data-offer-code="<?= htmlspecialchars(gawdee_setting('offer_code', 'FREEDOM10')) ?>" data-offer-percent="<?= (int) gawdee_setting('offer_percent', '10') ?>">
        <div class="admin-card__header"><div><span class="section-kicker">Assisted sale</span><h2>Create a phone or counter order</h2><p>Prices, offers, delivery and stock are recalculated by the same secure order engine as checkout.</p></div><span class="status-pill"><i class="ph ph-shield-check"></i> Inventory protected</span></div>
        <form method="post" class="admin-form order-create-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="create_admin_order"><input type="hidden" name="checkout_token" value="<?= htmlspecialchars($adminOrderToken) ?>">
            <fieldset><legend><span>1</span> Customer</legend><div class="form-grid form-grid--3"><label><span>Full name</span><input name="name" required autocomplete="name"></label><label><span>Email</span><input type="email" name="email" required autocomplete="email"></label><label><span>Phone</span><input name="phone" required autocomplete="tel"></label></div></fieldset>
            <fieldset><legend><span>2</span> Delivery</legend><div class="form-grid form-grid--3"><label class="form-span-2"><span>Address</span><input name="address1" required autocomplete="address-line1"></label><label><span>Address line 2</span><input name="address2" autocomplete="address-line2"></label><label><span>City</span><input name="city" required autocomplete="address-level2"></label><label><span>State</span><input name="state" required autocomplete="address-level1"></label><label><span>Pincode</span><input name="pincode" required pattern="[1-9][0-9]{5}" maxlength="6" inputmode="numeric" autocomplete="postal-code"></label></div></fieldset>
            <fieldset><legend><span>3</span> Products</legend><div class="order-builder-lines" data-order-lines><div class="order-builder-line"><label><span>Product</span><select name="product_id[]" required data-order-product><option value="">Choose a product</option><?php foreach ($orderProducts as $product): ?><option value="<?= htmlspecialchars($product['id']) ?>" data-price="<?= (int) $product['price'] ?>" data-stock="<?= (int) $product['stock'] ?>"><?= htmlspecialchars($product['name'] . ' · ' . $product['weight']) ?> — ₹<?= number_format((int) $product['price']) ?> (<?= (int) $product['stock'] ?> available)</option><?php endforeach; ?></select></label><label><span>Quantity</span><input type="number" name="quantity[]" min="1" max="10" value="1" required data-order-quantity></label><strong data-line-total>₹0</strong><button class="admin-action-icon admin-action-icon--danger" type="button" data-remove-line aria-label="Remove product"><i class="ph ph-trash"></i></button></div></div><button class="admin-button admin-button--ghost" type="button" data-add-line><i class="ph ph-plus"></i> Add another product</button></fieldset>
            <fieldset><legend><span>4</span> Payment & notes</legend><div class="form-grid form-grid--3"><label><span>Payment state</span><select name="payment_state"><option value="cod">Cash on delivery</option><option value="paid">Payment already collected</option></select></label><label><span>Offer code</span><input name="coupon_code" data-order-coupon placeholder="<?= htmlspecialchars(gawdee_setting('offer_code', 'FREEDOM10')) ?>"></label><label><span>Order note</span><input name="notes" maxlength="500" placeholder="Delivery instruction or gift note"></label></div></fieldset>
            <footer class="order-builder-footer"><div class="order-builder-totals"><span><small>Subtotal</small><strong data-builder-subtotal>₹0</strong></span><span><small>Discount</small><strong data-builder-discount>₹0</strong></span><span><small>Delivery</small><strong data-builder-shipping>₹0</strong></span><span class="is-total"><small>Order total</small><strong data-builder-total>₹0</strong></span></div><button class="admin-button admin-button--primary" type="submit"><i class="ph ph-check-circle"></i> Create & confirm order</button></footer>
        </form>
    </section>
<?php endif; ?>

<div class="order-metric-grid">
    <article><i class="ph ph-receipt"></i><span>All orders</span><strong><?= (int) ($orderMetrics['total'] ?? 0) ?></strong></article>
    <article><i class="ph ph-calendar-check"></i><span>Received today</span><strong><?= (int) ($orderMetrics['today'] ?? 0) ?></strong></article>
    <article class="is-attention"><i class="ph ph-warning-circle"></i><span>Needs attention</span><strong><?= (int) ($orderMetrics['attention'] ?? 0) ?></strong></article>
    <article><i class="ph ph-package"></i><span>To pack / ship</span><strong><?= (int) ($orderMetrics['fulfillment'] ?? 0) ?></strong></article>
    <article><i class="ph ph-currency-inr"></i><span>Paid revenue</span><strong>₹<?= number_format((int) ($orderMetrics['paid_revenue'] ?? 0)) ?></strong></article>
</div>

<?php if ($detailOrder):
    $allowedTransitions = gawdee_order_allowed_transitions($detailOrder);
    $trackingReference = gawdee_order_tracking_reference($detailOrder);
    $trackingUrl = gawdee_order_tracking_url($detailOrder);
    $canDispatch = in_array($detailOrder['status'], ['processing', 'packed'], true)
        && ($detailOrder['payment_method'] === 'cod' || $detailOrder['payment_status'] === 'paid');
?>
    <section class="order-workbench">
        <header class="order-workbench__header">
            <div><a href="?view=orders"><i class="ph ph-arrow-left"></i> Back to queue</a><span>Order <?= htmlspecialchars($detailOrder['order_number']) ?></span><h3><?= htmlspecialchars($detailOrder['customer_name']) ?></h3><p>Received <?= htmlspecialchars(date('j M Y, g:i a', strtotime((string) $detailOrder['created_at']))) ?></p></div>
            <div class="order-workbench__header-actions"><div class="order-workbench__badges"><span class="status-pill status-pill--<?= htmlspecialchars($detailOrder['status']) ?>"><?= htmlspecialchars($statusLabels[$detailOrder['status']] ?? $detailOrder['status']) ?></span><span class="status-pill status-pill--<?= htmlspecialchars($detailOrder['payment_status']) ?>"><?= htmlspecialchars(str_replace('_', ' ', $detailOrder['payment_status'])) ?></span><span class="status-pill status-pill--source"><?= htmlspecialchars($detailOrder['source'] === 'admin' ? 'Admin order' : 'Online order') ?></span></div><div class="admin-actions"><a class="admin-button admin-button--light" href="order-print.php?order=<?= (int) $detailOrder['id'] ?>&type=invoice" target="_blank"><i class="ph ph-printer"></i> Invoice</a><a class="admin-button admin-button--light" href="order-print.php?order=<?= (int) $detailOrder['id'] ?>&type=packing" target="_blank"><i class="ph ph-package"></i> Packing slip</a></div></div>
        </header>

        <div class="order-workbench__grid">
            <main>
                <section class="admin-card order-items-card"><div class="admin-card__header"><div><h2>Order items</h2><p><?= count($detailItems) ?> product line<?= count($detailItems) === 1 ? '' : 's' ?></p></div><strong>₹<?= number_format((int) $detailOrder['total']) ?></strong></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Product</th><th>Qty</th><th>Unit price</th><th>Line total</th></tr></thead><tbody><?php foreach ($detailItems as $item): ?><tr><td><div class="admin-table__product"><img src="../<?= htmlspecialchars($item['image']) ?>" alt=""><strong><?= htmlspecialchars($item['product_name']) ?></strong></div></td><td><?= (int) $item['quantity'] ?></td><td>₹<?= number_format((int) $item['unit_price']) ?></td><td><strong>₹<?= number_format((int) $item['unit_price'] * (int) $item['quantity']) ?></strong></td></tr><?php endforeach; ?></tbody></table></div><div class="order-price-breakdown"><p><span>Subtotal</span><strong>₹<?= number_format((int) $detailOrder['subtotal']) ?></strong></p><?php if ((int) $detailOrder['discount'] > 0): ?><p class="is-discount"><span>Offer <?= htmlspecialchars($detailOrder['coupon_code']) ?></span><strong>−₹<?= number_format((int) $detailOrder['discount']) ?></strong></p><?php endif; ?><p><span>Delivery</span><strong><?= (int) $detailOrder['shipping'] === 0 ? 'Free' : '₹' . number_format((int) $detailOrder['shipping']) ?></strong></p><p class="is-total"><span>Order total</span><strong>₹<?= number_format((int) $detailOrder['total']) ?></strong></p></div></section>

                <section class="admin-card order-timeline-card"><div class="admin-card__header"><div><h2>Activity timeline</h2><p>Permanent order and payment history</p></div></div><div class="order-admin-timeline"><?php foreach ($detailEvents as $event): ?><article><span></span><div><strong><?= htmlspecialchars($event['title']) ?></strong><p><?= htmlspecialchars($event['description']) ?></p></div><time><?= htmlspecialchars(date('j M, g:i a', strtotime((string) $event['created_at']))) ?></time></article><?php endforeach; ?></div></section>
            </main>

            <aside class="order-workbench__aside">
                <section class="admin-card"><div class="admin-card__header"><div><h2>Customer & delivery</h2><p>Contact and destination</p></div></div><div class="order-contact-card"><p><i class="ph ph-user"></i><span><strong><?= htmlspecialchars($detailOrder['customer_name']) ?></strong><?= htmlspecialchars($detailOrder['email']) ?><br><?= htmlspecialchars($detailOrder['phone']) ?></span></p><p><i class="ph ph-map-pin"></i><span><?= htmlspecialchars($detailOrder['address1']) ?><br><?php if ($detailOrder['address2']): ?><?= htmlspecialchars($detailOrder['address2']) ?><br><?php endif; ?><?= htmlspecialchars($detailOrder['city'] . ', ' . $detailOrder['state'] . ' ' . $detailOrder['pincode']) ?></span></p><?php if ($detailOrder['notes']): ?><p><i class="ph ph-note"></i><span><strong>Customer note</strong><?= nl2br(htmlspecialchars($detailOrder['notes'])) ?></span></p><?php endif; ?></div></section>

                <section class="admin-card"><div class="admin-card__header"><div><h2>Workflow controls</h2><p>Only valid next steps are available</p></div></div><div class="order-control-stack">
                    <div class="order-control-summary"><span><i class="ph ph-credit-card"></i><strong><?= htmlspecialchars(match ($detailOrder['payment_method']) { 'cod' => 'Cash on delivery', 'manual' => 'Manual payment', default => 'Razorpay' }) ?></strong><small><?= htmlspecialchars(str_replace('_', ' ', $detailOrder['payment_status'])) ?></small></span><span><i class="ph ph-package"></i><strong><?= htmlspecialchars(ucfirst($detailOrder['fulfillment_mode'])) ?></strong><small><?= htmlspecialchars(str_replace('_', ' ', $detailOrder['shipment_status'])) ?></small></span><span><i class="ph ph-cube"></i><strong>Inventory</strong><small><?= htmlspecialchars(str_replace('_', ' ', $detailOrder['inventory_status'])) ?></small></span></div>

                    <form method="post" class="admin-form private-note-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="save_order_note"><input type="hidden" name="id" value="<?= (int) $detailOrder['id'] ?>"><label><span><i class="ph ph-lock-key"></i> Private operations note</span><textarea name="admin_note" maxlength="1000" placeholder="Visible only to administrators"><?= htmlspecialchars($detailOrder['admin_note']) ?></textarea></label><button class="admin-button admin-button--ghost admin-button--full">Save private note</button></form>

                    <?php if ($detailOrder['payment_method'] === 'cod' && $detailOrder['payment_status'] !== 'paid' && !in_array($detailOrder['status'], ['cancelled','refunded'], true)): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="mark_cod_paid"><input type="hidden" name="id" value="<?= (int) $detailOrder['id'] ?>"><button class="admin-button admin-button--secondary admin-button--full"><i class="ph ph-hand-coins"></i> Mark COD collected</button></form><?php endif; ?>

                    <?php if ($allowedTransitions): ?><form method="post" class="admin-form order-status-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="update_order"><input type="hidden" name="id" value="<?= (int) $detailOrder['id'] ?>"><label><span>Next status</span><select name="status" required><?php foreach ($allowedTransitions as $status): if ($status === 'shipped') continue; ?><option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status)) ?></option><?php endforeach; ?></select></label><label><span>Internal/customer timeline note</span><textarea name="note" placeholder="Optional reason or fulfilment note"></textarea></label><button class="admin-button admin-button--primary admin-button--full">Update workflow <i class="ph ph-arrow-right"></i></button></form><?php endif; ?>

                    <?php if ($canDispatch): ?><details class="dispatch-panel"><summary><i class="ph ph-truck"></i> Dispatch order</summary><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="manual_shipment"><input type="hidden" name="id" value="<?= (int) $detailOrder['id'] ?>"><label><span>Courier / delivery method</span><input name="courier_name" required placeholder="Example: Delhivery or Local delivery"></label><label><span>Tracking reference</span><input name="tracking_number" placeholder="Optional AWB / reference"></label><label><span>Tracking URL</span><input type="url" name="tracking_url" placeholder="Optional https://..."></label><button class="admin-button admin-button--primary admin-button--full">Mark manually dispatched</button></form><?php if ($delhiveryReady && empty($detailOrder['delhivery_waybill'])): ?><div class="dispatch-divider"><span>or</span></div><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="create_delhivery_shipment"><input type="hidden" name="id" value="<?= (int) $detailOrder['id'] ?>"><button class="admin-button admin-button--secondary admin-button--full"><i class="ph ph-package"></i> Book with Delhivery</button></form><?php elseif ($dtdcReady && !$detailOrder['dtdc_reference']): ?><div class="dispatch-divider"><span>or</span></div><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="create_shipment"><input type="hidden" name="id" value="<?= (int) $detailOrder['id'] ?>"><button class="admin-button admin-button--secondary admin-button--full"><i class="ph ph-truck"></i> Book with DTDC</button></form><?php elseif (!$delhiveryReady && !$dtdcReady): ?><p class="dispatch-offline-note"><i class="ph ph-info"></i> Courier APIs are offline. Manual dispatch remains fully available.</p><?php endif; ?></details><?php endif; ?>
                </div></section>

                <?php if ($trackingReference || $trackingUrl): ?><section class="tracking-admin-card"><i class="ph ph-map-trifold"></i><div><span>Shipment tracking</span><strong><?= htmlspecialchars($detailOrder['courier_name'] ?: 'Courier') ?><?= $trackingReference ? ' · ' . htmlspecialchars($trackingReference) : '' ?></strong><?php if (!empty($detailOrder['delhivery_last_status'])): ?><small><?= htmlspecialchars($detailOrder['delhivery_last_status']) ?><?= !empty($detailOrder['delhivery_last_sync_at']) ? ' · synced ' . htmlspecialchars($detailOrder['delhivery_last_sync_at']) : '' ?></small><?php endif; ?><div style="display:flex;gap:8px;flex-wrap:wrap"><?php if ($trackingUrl): ?><a href="<?= htmlspecialchars($trackingUrl) ?>" target="_blank" rel="noopener">Open tracking <i class="ph ph-arrow-up-right"></i></a><?php endif; ?><?php if (!empty($detailOrder['delhivery_waybill'])): ?><a href="delhivery-label.php?order=<?= (int) $detailOrder['id'] ?>" target="_blank">Print label <i class="ph ph-printer"></i></a><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="sync_delhivery"><input type="hidden" name="id" value="<?= (int) $detailOrder['id'] ?>"><button class="admin-button admin-button--ghost" style="padding:6px 10px">Sync now</button></form><?php endif; ?></div></div></section><?php endif; ?>
            </aside>
        </div>
    </section>
<?php else: ?>
    <section class="admin-card order-queue-card">
        <form method="get" class="order-filter-bar"><input type="hidden" name="view" value="orders"><label class="order-filter-search"><i class="ph ph-magnifying-glass"></i><input name="q" value="<?= htmlspecialchars($orderFilters['q']) ?>" placeholder="Search order, customer, phone or tracking"></label><select name="status"><option value="">All order states</option><?php foreach ($statusLabels as $value => $label): ?><option value="<?= htmlspecialchars($value) ?>" <?= $orderFilters['status'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select><select name="payment_status"><option value="">All payments</option><?php foreach (['initializing','pending','paid','cod_pending','failed','expired','refunded'] as $value): ?><option value="<?= $value ?>" <?= $orderFilters['payment_status'] === $value ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst(str_replace('_',' ',$value))) ?></option><?php endforeach; ?></select><select name="fulfillment_mode"><option value="">All fulfilment</option><option value="manual" <?= $orderFilters['fulfillment_mode']==='manual'?'selected':'' ?>>Manual</option><option value="delhivery" <?= $orderFilters['fulfillment_mode']==='delhivery'?'selected':'' ?>>Delhivery</option><option value="dtdc" <?= $orderFilters['fulfillment_mode']==='dtdc'?'selected':'' ?>>DTDC</option></select><button class="admin-button admin-button--primary">Filter</button><?php if (array_filter($orderFilters)): ?><a class="admin-button admin-button--ghost" href="?view=orders">Clear</a><?php endif; ?></form>
        <?php if ($orders): ?><div class="admin-table-wrap"><table class="admin-table order-queue-table"><thead><tr><th>Order</th><th>Customer</th><th>Payment</th><th>Fulfilment</th><th>Total</th><th>Received</th><th></th></tr></thead><tbody><?php foreach ($orders as $order): $tracking = gawdee_order_tracking_reference($order); ?><tr class="<?= in_array($order['status'], ['pending','on_hold'], true) || in_array($order['payment_status'], ['initializing','failed'], true) ? 'needs-attention' : '' ?>"><td><a class="order-number-link" href="?view=orders&order=<?= (int) $order['id'] ?>"><span><?= htmlspecialchars($order['order_number']) ?></span><small><?= htmlspecialchars($statusLabels[$order['status']] ?? $order['status']) ?></small></a></td><td><strong><?= htmlspecialchars($order['customer_name']) ?></strong><small><?= htmlspecialchars($order['phone']) ?><br><?= htmlspecialchars($order['email']) ?></small></td><td><span class="status-pill status-pill--<?= htmlspecialchars($order['payment_status']) ?>"><?= htmlspecialchars(str_replace('_', ' ', $order['payment_status'])) ?></span><small><?= htmlspecialchars(strtoupper($order['payment_method'])) ?></small></td><td><strong><?= htmlspecialchars(ucfirst($order['fulfillment_mode'])) ?></strong><small><?= htmlspecialchars(str_replace('_', ' ', $order['shipment_status'])) ?><?= $tracking ? '<br>' . htmlspecialchars($tracking) : '' ?></small></td><td><strong>₹<?= number_format((int) $order['total']) ?></strong><?php if ((int) $order['discount'] > 0): ?><small><?= htmlspecialchars($order['coupon_code']) ?> applied</small><?php endif; ?></td><td><span><?= htmlspecialchars(date('j M Y', strtotime((string) $order['created_at']))) ?></span><small><?= htmlspecialchars(date('g:i a', strtotime((string) $order['created_at']))) ?></small></td><td><a class="admin-action-icon" href="?view=orders&order=<?= (int) $order['id'] ?>" title="Open order"><i class="ph ph-arrow-right"></i></a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><i class="ph ph-receipt"></i><h3><?= array_filter($orderFilters) ? 'No orders match these filters' : 'No orders yet' ?></h3><p><?= array_filter($orderFilters) ? 'Clear the filters to return to the complete queue.' : 'COD and Razorpay orders appear here immediately, even while courier integrations are offline.' ?></p></div><?php endif; ?>
    </section>
<?php endif; ?>
