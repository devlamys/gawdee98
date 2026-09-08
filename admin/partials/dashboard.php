<?php

$db = gawdee_db();
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$lowStockThreshold = max(1, (int) gawdee_setting('low_stock_threshold', '12'));
$dashboardMetrics = $db->query(<<<'SQL'
SELECT
    COUNT(*) AS total_orders,
    SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS lifetime_revenue,
    SUM(CASE WHEN payment_status='paid' AND strftime('%Y-%m', paid_at)=strftime('%Y-%m','now') THEN total ELSE 0 END) AS month_revenue,
    SUM(CASE WHEN payment_status='paid' AND strftime('%Y-%m', paid_at)=strftime('%Y-%m','now','-1 month') THEN total ELSE 0 END) AS last_month_revenue,
    SUM(CASE WHEN date(created_at)=date('now') THEN 1 ELSE 0 END) AS today_orders,
    SUM(CASE WHEN status IN ('pending','on_hold') OR payment_status IN ('initializing','failed') THEN 1 ELSE 0 END) AS attention,
    SUM(CASE WHEN status IN ('processing','packed') THEN 1 ELSE 0 END) AS to_fulfil,
    ROUND(AVG(CASE WHEN payment_status='paid' THEN total END)) AS average_order
FROM orders
SQL)->fetch() ?: [];

$monthRevenue = (int) ($dashboardMetrics['month_revenue'] ?? 0);
$lastMonthRevenue = (int) ($dashboardMetrics['last_month_revenue'] ?? 0);
$revenueChange = $lastMonthRevenue > 0 ? (int) round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100) : null;
$customerCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$activeProducts = (int) $db->query('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn();
$lowStockProducts = (int) $db->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock <= ' . $lowStockThreshold)->fetchColumn();

$dailyRows = $db->query("SELECT date(created_at) AS day, COUNT(*) AS orders, SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS revenue FROM orders WHERE created_at >= date('now','-13 days') GROUP BY date(created_at)")->fetchAll();
$dailyByDate = [];
foreach ($dailyRows as $row) {
    $dailyByDate[$row['day']] = $row;
}
$dailySeries = [];
for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
    $date = date('Y-m-d', strtotime('-' . $daysAgo . ' days'));
    $dailySeries[] = ['date' => $date, 'orders' => (int) ($dailyByDate[$date]['orders'] ?? 0), 'revenue' => (int) ($dailyByDate[$date]['revenue'] ?? 0)];
}
$maxDailyRevenue = max(1, ...array_column($dailySeries, 'revenue'));
$periodRevenue = array_sum(array_column($dailySeries, 'revenue'));
$periodOrders = array_sum(array_column($dailySeries, 'orders'));

$pipelineRows = $db->query('SELECT status, COUNT(*) AS total FROM orders GROUP BY status')->fetchAll();
$pipelineCounts = array_fill_keys(array_keys(gawdee_order_status_labels()), 0);
foreach ($pipelineRows as $row) {
    $pipelineCounts[$row['status']] = (int) $row['total'];
}
$pipeline = [
    ['processing', 'Confirmed', 'ph-check-circle'],
    ['packed', 'Packed', 'ph-package'],
    ['shipped', 'In transit', 'ph-truck'],
    ['delivered', 'Delivered', 'ph-house-line'],
];
$pipelineMax = max(1, ...array_map(static fn (array $item): int => $pipelineCounts[$item[0]] ?? 0, $pipeline));

$recentOrders = $db->query('SELECT * FROM orders ORDER BY id DESC LIMIT 6')->fetchAll();
$lowStockList = $db->query('SELECT id, name, image, stock, stock_status FROM products WHERE is_active=1 AND stock <= ' . $lowStockThreshold . ' ORDER BY stock ASC, name LIMIT 6')->fetchAll();
$topProducts = $db->query(<<<'SQL'
SELECT oi.product_id, oi.product_name, oi.image, SUM(oi.quantity) AS units, SUM(oi.quantity * oi.unit_price) AS sales
FROM order_items oi
JOIN orders o ON o.id=oi.order_id
WHERE o.status NOT IN ('cancelled','refunded')
GROUP BY oi.product_id, oi.product_name, oi.image
ORDER BY units DESC, sales DESC
LIMIT 4
SQL)->fetchAll();
$statusLabels = gawdee_order_status_labels();
?>

<section class="command-hero">
    <div><span class="section-kicker">Live commerce overview</span><h2><?= htmlspecialchars('Welcome back, ' . explode(' ', $admin['name'])[0]) ?>!</h2><p>Here’s what is happening across Gawdee today.</p></div>
    <div class="command-hero__actions"><span class="command-date"><i class="ph ph-calendar-blank"></i><?= htmlspecialchars(date('M j, Y', strtotime('-6 days'))) ?> – <?= htmlspecialchars(date('M j, Y')) ?></span><a class="admin-button admin-button--gold" href="?view=orders&new=1"><i class="ph ph-plus"></i> Create order</a></div>
</section>

<div class="command-stat-grid">
    <article class="command-stat"><div><span>Revenue this month</span><i class="ph ph-currency-inr"></i></div><strong>₹<?= number_format($monthRevenue) ?></strong><small class="<?= $revenueChange !== null && $revenueChange < 0 ? 'is-down' : 'is-up' ?>"><?php if ($revenueChange === null): ?>First recorded month<?php else: ?><i class="ph <?= $revenueChange < 0 ? 'ph-trend-down' : 'ph-trend-up' ?>"></i> <?= abs($revenueChange) ?>% vs last month<?php endif; ?></small></article>
    <article class="command-stat"><div><span>Orders today</span><i class="ph ph-shopping-bag"></i></div><strong><?= (int) ($dashboardMetrics['today_orders'] ?? 0) ?></strong><small><?= (int) ($dashboardMetrics['total_orders'] ?? 0) ?> orders all time</small></article>
    <article class="command-stat"><div><span>To pack or ship</span><i class="ph ph-package"></i></div><strong><?= (int) ($dashboardMetrics['to_fulfil'] ?? 0) ?></strong><small><a href="?view=orders&status=processing">Open fulfilment queue <i class="ph ph-arrow-right"></i></a></small></article>
    <article class="command-stat <?= (int) ($dashboardMetrics['attention'] ?? 0) > 0 ? 'is-attention' : '' ?>"><div><span>Needs attention</span><i class="ph ph-warning-circle"></i></div><strong><?= (int) ($dashboardMetrics['attention'] ?? 0) ?></strong><small><a href="?view=orders&status=on_hold">Review flagged orders <i class="ph ph-arrow-right"></i></a></small></article>
</div>

<div class="command-layout">
    <section class="admin-card revenue-card">
        <div class="admin-card__header"><div><span class="section-kicker">Performance</span><h2>14-day revenue</h2><p>Paid orders only</p></div><div class="revenue-card__summary"><strong>₹<?= number_format($periodRevenue) ?></strong><span><?= $periodOrders ?> order<?= $periodOrders === 1 ? '' : 's' ?></span></div></div>
        <div class="revenue-chart" role="img" aria-label="Paid revenue for the last 14 days">
            <?php foreach ($dailySeries as $point): $height = max(4, (int) round(($point['revenue'] / $maxDailyRevenue) * 100)); ?><div class="revenue-chart__day" title="<?= htmlspecialchars(date('j M', strtotime($point['date'])) . ': ₹' . number_format($point['revenue'])) ?>"><span><i style="height:<?= $height ?>%"></i></span><small><?= htmlspecialchars(date('j', strtotime($point['date']))) ?></small></div><?php endforeach; ?>
        </div>
        <div class="revenue-chart__legend"><span><?= htmlspecialchars(date('j M', strtotime($dailySeries[0]['date']))) ?></span><span>Hover a bar for daily revenue</span><span>Today</span></div>
    </section>

    <section class="admin-card pipeline-card">
        <div class="admin-card__header"><div><span class="section-kicker">Operations</span><h2>Fulfilment pipeline</h2><p>Current order workload</p></div></div>
        <div class="pipeline-list"><?php foreach ($pipeline as [$key, $label, $icon]): $count = $pipelineCounts[$key] ?? 0; ?><a href="?view=orders&status=<?= $key ?>"><i class="ph <?= $icon ?>"></i><span><strong><?= $label ?></strong><em><b style="width:<?= (int) round(($count / $pipelineMax) * 100) ?>%"></b></em></span><b><?= $count ?></b></a><?php endforeach; ?></div>
    </section>
</div>

<nav class="dashboard-quick-actions" aria-label="Quick actions">
    <a href="?view=products&edit=new"><i class="ph ph-cube"></i><span>Add Product</span></a>
    <a href="?view=orders&new=1"><i class="ph ph-receipt"></i><span>Create Order</span></a>
    <a href="?view=orders"><i class="ph ph-shopping-cart"></i><span>View Orders</span></a>
    <a href="?view=inventory"><i class="ph ph-stack"></i><span>Inventory</span></a>
    <a href="?view=customers"><i class="ph ph-user-circle"></i><span>Customers</span></a>
    <a href="?view=blog&edit=-1"><i class="ph ph-article"></i><span>New Blog Post</span></a>
</nav>

<div class="command-bottom-grid">
    <section class="admin-card"><div class="admin-card__header"><div><span class="section-kicker">Latest activity</span><h2>Recent orders</h2></div><a class="text-link" href="?view=orders">View all <i class="ph ph-arrow-right"></i></a></div><?php if ($recentOrders): ?><div class="admin-table-wrap"><table class="admin-table command-orders-table"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($recentOrders as $order): ?><tr><td><a class="order-number-link" href="?view=orders&order=<?= (int) $order['id'] ?>"><span><?= htmlspecialchars($order['order_number']) ?></span><small><?= htmlspecialchars(date('j M, g:i a', strtotime($order['created_at']))) ?></small></a></td><td><strong><?= htmlspecialchars($order['customer_name']) ?></strong><small><?= htmlspecialchars($order['source'] === 'admin' ? 'Admin order' : 'Online checkout') ?></small></td><td><strong>₹<?= number_format((int) $order['total']) ?></strong></td><td><span class="status-pill status-pill--<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($statusLabels[$order['status']] ?? $order['status']) ?></span></td><td><a class="admin-action-icon" href="?view=orders&order=<?= (int) $order['id'] ?>"><i class="ph ph-arrow-right"></i></a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><i class="ph ph-shopping-bag-open"></i><h3>No orders yet</h3><p>Your first checkout will appear here immediately.</p></div><?php endif; ?></section>

    <div class="command-side-stack">
        <section class="admin-card stock-watch-card"><div class="admin-card__header"><div><span class="section-kicker">Stock watch</span><h2>Low inventory</h2></div><a class="text-link" href="?view=inventory">Inventory <i class="ph ph-arrow-right"></i></a></div><?php if ($lowStockList): ?><div class="stock-watch-list"><?php foreach ($lowStockList as $product): ?><a href="?view=inventory&adjust=<?= rawurlencode($product['id']) ?>"><img src="../<?= htmlspecialchars($product['image']) ?>" alt=""><span><strong><?= htmlspecialchars($product['name']) ?></strong><small><?= (int) $product['stock'] ?> unit<?= (int) $product['stock'] === 1 ? '' : 's' ?> left</small></span><b class="<?= (int) $product['stock'] === 0 ? 'is-out' : '' ?>"><?= (int) $product['stock'] ?></b></a><?php endforeach; ?></div><?php else: ?><div class="mini-empty"><i class="ph ph-check-circle"></i><span><strong>Stock levels look healthy</strong>No active product is below <?= $lowStockThreshold ?> units.</span></div><?php endif; ?></section>
        <section class="system-strip"><span><i class="ph ph-storefront"></i><strong><?= $activeProducts ?></strong> live products</span><span><i class="ph ph-users"></i><strong><?= $customerCount ?></strong> customers</span><span><i class="ph ph-receipt"></i><strong>₹<?= number_format((int) ($dashboardMetrics['average_order'] ?? 0)) ?></strong> average order</span></section>
    </div>
</div>

<?php if ($topProducts): ?><section class="top-products-strip"><div><span class="section-kicker">Customer favourites</span><h2>Best-selling products</h2></div><div class="top-products-list"><?php foreach ($topProducts as $index => $product): ?><article><span><?= $index + 1 ?></span><img src="../<?= htmlspecialchars($product['image']) ?>" alt=""><div><strong><?= htmlspecialchars($product['product_name']) ?></strong><small><?= (int) $product['units'] ?> units · ₹<?= number_format((int) $product['sales']) ?></small></div></article><?php endforeach; ?></div></section><?php endif; ?>
