<?php

$db = gawdee_db();
$customerSearch = trim((string) ($_GET['q'] ?? ''));
$registeredRows = $db->query("SELECT * FROM users WHERE role='customer' ORDER BY id DESC")->fetchAll();
$orderCustomerRows = $db->query(<<<'SQL'
SELECT lower(email) AS email_key, MAX(email) AS email, MAX(customer_name) AS customer_name, MAX(phone) AS phone,
       COUNT(*) AS order_count,
       SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS paid_spend,
       MAX(created_at) AS last_order_at
FROM orders
GROUP BY lower(email)
SQL)->fetchAll();
$customersByEmail = [];
foreach ($registeredRows as $user) {
    $key = strtolower((string) $user['email']);
    $customersByEmail[$key] = [
        'name' => $user['name'], 'email' => $user['email'], 'phone' => $user['phone'],
        'registered' => true, 'created_at' => $user['created_at'], 'last_login_at' => $user['last_login_at'],
        'order_count' => 0, 'paid_spend' => 0, 'last_order_at' => '',
    ];
}
foreach ($orderCustomerRows as $row) {
    $key = (string) $row['email_key'];
    if (!isset($customersByEmail[$key])) {
        $customersByEmail[$key] = ['name' => $row['customer_name'], 'email' => $row['email'], 'phone' => $row['phone'], 'registered' => false, 'created_at' => $row['last_order_at'], 'last_login_at' => null];
    }
    $customersByEmail[$key]['order_count'] = (int) $row['order_count'];
    $customersByEmail[$key]['paid_spend'] = (int) $row['paid_spend'];
    $customersByEmail[$key]['last_order_at'] = $row['last_order_at'];
    if ($customersByEmail[$key]['phone'] === '') {
        $customersByEmail[$key]['phone'] = $row['phone'];
    }
}
$customers = array_values($customersByEmail);
if ($customerSearch !== '') {
    $needle = mb_strtolower($customerSearch);
    $customers = array_values(array_filter($customers, static fn (array $customer): bool => str_contains(mb_strtolower($customer['name'] . ' ' . $customer['email'] . ' ' . $customer['phone']), $needle)));
}
usort($customers, static fn (array $a, array $b): int => strcmp((string) ($b['last_order_at'] ?: $b['created_at']), (string) ($a['last_order_at'] ?: $a['created_at'])));
$repeatCustomers = count(array_filter($customersByEmail, static fn (array $customer): bool => $customer['order_count'] > 1));
$customerRevenue = array_sum(array_column($customersByEmail, 'paid_spend'));
$customerWithSpend = max(1, count(array_filter($customersByEmail, static fn (array $customer): bool => $customer['paid_spend'] > 0)));

$selectedEmail = trim((string) ($_GET['customer'] ?? ''));
$selectedCustomer = $selectedEmail !== '' ? ($customersByEmail[strtolower($selectedEmail)] ?? null) : null;
$selectedOrders = [];
$selectedUser = null;
if ($selectedCustomer) {
    $customerOrdersStatement = $db->prepare('SELECT * FROM orders WHERE lower(email)=lower(?) ORDER BY id DESC');
    $customerOrdersStatement->execute([$selectedCustomer['email']]);
    $selectedOrders = $customerOrdersStatement->fetchAll();
    $customerUserStatement = $db->prepare("SELECT * FROM users WHERE role='customer' AND lower(email)=lower(?) LIMIT 1");
    $customerUserStatement->execute([$selectedCustomer['email']]);
    $selectedUser = $customerUserStatement->fetch() ?: null;
}
$statusLabels = gawdee_order_status_labels();
?>

<div class="admin-section-title customers-heading"><div><span class="section-kicker">Customer intelligence</span><h2>Customers</h2><p>Registered accounts and guest buyers in one complete view.</p></div><a class="admin-button admin-button--primary" href="?view=orders&new=1"><i class="ph ph-plus"></i> Create order</a></div>

<div class="customer-metrics"><article><i class="ph ph-users-three"></i><span>Known customers</span><strong><?= count($customersByEmail) ?></strong></article><article><i class="ph ph-user-circle-check"></i><span>Registered accounts</span><strong><?= count($registeredRows) ?></strong></article><article><i class="ph ph-arrows-clockwise"></i><span>Repeat buyers</span><strong><?= $repeatCustomers ?></strong></article><article><i class="ph ph-wallet"></i><span>Average paid value</span><strong>₹<?= number_format((int) round($customerRevenue / $customerWithSpend)) ?></strong></article></div>

<?php if ($selectedCustomer): ?>
    <section class="customer-profile">
        <header><a href="?view=customers"><i class="ph ph-arrow-left"></i> Back to customers</a><div class="customer-profile__identity"><span><?= htmlspecialchars(strtoupper(substr($selectedCustomer['name'], 0, 1))) ?></span><div><small><?= $selectedCustomer['registered'] ? 'Registered customer' : 'Guest customer' ?></small><h2><?= htmlspecialchars($selectedCustomer['name']) ?></h2><p><?= htmlspecialchars($selectedCustomer['email']) ?> · <?= htmlspecialchars($selectedCustomer['phone']) ?></p></div></div><div class="customer-profile__value"><span>Customer value</span><strong>₹<?= number_format($selectedCustomer['paid_spend']) ?></strong><small><?= $selectedCustomer['order_count'] ?> order<?= $selectedCustomer['order_count'] === 1 ? '' : 's' ?></small></div></header>
        <div class="customer-profile__grid"><section class="admin-card"><div class="admin-card__header"><div><span class="section-kicker">Order history</span><h2>Orders</h2></div></div><?php if ($selectedOrders): ?><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order</th><th>Date</th><th>Payment</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($selectedOrders as $order): ?><tr><td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td><td><?= htmlspecialchars(date('j M Y', strtotime($order['created_at']))) ?></td><td><span class="status-pill status-pill--<?= htmlspecialchars($order['payment_status']) ?>"><?= htmlspecialchars(str_replace('_', ' ', $order['payment_status'])) ?></span></td><td><strong>₹<?= number_format((int) $order['total']) ?></strong></td><td><?= htmlspecialchars($statusLabels[$order['status']] ?? $order['status']) ?></td><td><a class="admin-action-icon" href="?view=orders&order=<?= (int) $order['id'] ?>"><i class="ph ph-arrow-right"></i></a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><i class="ph ph-receipt"></i><h3>No orders yet</h3></div><?php endif; ?></section><aside class="admin-card"><div class="admin-card__header"><div><span class="section-kicker">Profile</span><h2>Customer details</h2></div></div><div class="customer-detail-list"><p><i class="ph ph-envelope"></i><span><strong>Email</strong><?= htmlspecialchars($selectedCustomer['email']) ?></span></p><p><i class="ph ph-phone"></i><span><strong>Phone</strong><?= htmlspecialchars($selectedCustomer['phone'] ?: 'Not supplied') ?></span></p><?php if ($selectedUser && $selectedUser['address1']): ?><p><i class="ph ph-map-pin"></i><span><strong>Saved address</strong><?= htmlspecialchars($selectedUser['address1']) ?><?php if ($selectedUser['address2']): ?>, <?= htmlspecialchars($selectedUser['address2']) ?><?php endif; ?><br><?= htmlspecialchars($selectedUser['city'] . ', ' . $selectedUser['state'] . ' ' . $selectedUser['pincode']) ?></span></p><?php endif; ?><p><i class="ph ph-calendar"></i><span><strong>Customer since</strong><?= htmlspecialchars(date('j M Y', strtotime((string) $selectedCustomer['created_at']))) ?></span></p><?php if ($selectedCustomer['last_login_at']): ?><p><i class="ph ph-sign-in"></i><span><strong>Last sign in</strong><?= htmlspecialchars(date('j M Y, g:i a', strtotime((string) $selectedCustomer['last_login_at']))) ?></span></p><?php endif; ?></div><div class="customer-profile__actions"><a class="admin-button admin-button--secondary admin-button--full" href="mailto:<?= htmlspecialchars($selectedCustomer['email']) ?>"><i class="ph ph-envelope-simple"></i> Email customer</a><a class="admin-button admin-button--ghost admin-button--full" href="?view=orders&new=1"><i class="ph ph-plus"></i> Create another order</a></div></aside></div>
    </section>
<?php else: ?>
    <section class="admin-card customer-table-card"><form method="get" class="order-filter-bar"><input type="hidden" name="view" value="customers"><label class="order-filter-search"><i class="ph ph-magnifying-glass"></i><input name="q" value="<?= htmlspecialchars($customerSearch) ?>" placeholder="Search name, email or phone"></label><button class="admin-button admin-button--primary">Search</button><?php if ($customerSearch !== ''): ?><a class="admin-button admin-button--ghost" href="?view=customers">Clear</a><?php endif; ?></form><?php if ($customers): ?><div class="admin-table-wrap"><table class="admin-table customer-table"><thead><tr><th>Customer</th><th>Type</th><th>Orders</th><th>Paid value</th><th>Last activity</th><th></th></tr></thead><tbody><?php foreach ($customers as $customer): ?><tr><td><div class="customer-cell"><span><?= htmlspecialchars(strtoupper(substr($customer['name'], 0, 1))) ?></span><div><strong><?= htmlspecialchars($customer['name']) ?></strong><small><?= htmlspecialchars($customer['email']) ?><br><?= htmlspecialchars($customer['phone']) ?></small></div></div></td><td><span class="status-pill <?= $customer['registered'] ? '' : 'status-pill--draft' ?>"><?= $customer['registered'] ? 'Registered' : 'Guest' ?></span></td><td><strong><?= $customer['order_count'] ?></strong></td><td><strong>₹<?= number_format($customer['paid_spend']) ?></strong></td><td><?= htmlspecialchars(date('j M Y', strtotime((string) ($customer['last_order_at'] ?: $customer['created_at'])))) ?></td><td><a class="admin-action-icon" href="?view=customers&customer=<?= rawurlencode($customer['email']) ?>" title="Open customer"><i class="ph ph-arrow-right"></i></a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><i class="ph ph-users-three"></i><h3>No customers match</h3><p>Try a different name, email or phone number.</p></div><?php endif; ?></section>
<?php endif; ?>
