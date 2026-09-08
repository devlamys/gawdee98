<?php

$db = gawdee_db();
$lowStockThreshold = max(1, (int) gawdee_setting('low_stock_threshold', '12'));
$inventoryQuery = trim((string) ($_GET['q'] ?? ''));
$inventoryStatus = in_array($_GET['stock'] ?? '', ['low', 'out', 'healthy', 'hidden'], true) ? (string) $_GET['stock'] : '';
$where = [];
$params = [];
if ($inventoryQuery !== '') {
    $where[] = '(name LIKE ? OR full_name LIKE ? OR sku LIKE ? OR category LIKE ?)';
    $needle = '%' . $inventoryQuery . '%';
    array_push($params, $needle, $needle, $needle, $needle);
}
if ($inventoryStatus === 'low') {
    $where[] = 'is_active=1 AND stock > 0 AND stock <= ?';
    $params[] = $lowStockThreshold;
} elseif ($inventoryStatus === 'out') {
    $where[] = 'is_active=1 AND stock=0';
} elseif ($inventoryStatus === 'healthy') {
    $where[] = 'is_active=1 AND stock > ?';
    $params[] = $lowStockThreshold;
} elseif ($inventoryStatus === 'hidden') {
    $where[] = 'is_active=0';
}
$inventorySql = 'SELECT * FROM products' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY is_active DESC, stock ASC, name LIMIT 300';
$inventoryStatement = $db->prepare($inventorySql);
$inventoryStatement->execute($params);
$inventoryProducts = $inventoryStatement->fetchAll();
$inventoryMetrics = $db->query('SELECT COUNT(*) AS products, COALESCE(SUM(stock),0) AS units, COALESCE(SUM(stock * price),0) AS value, SUM(CASE WHEN is_active=1 AND stock=0 THEN 1 ELSE 0 END) AS out_count, SUM(CASE WHEN is_active=1 AND stock > 0 AND stock <= ' . $lowStockThreshold . ' THEN 1 ELSE 0 END) AS low_count FROM products')->fetch() ?: [];
$adjustId = trim((string) ($_GET['adjust'] ?? ''));
$adjustProduct = null;
if ($adjustId !== '') {
    $adjustStatement = $db->prepare('SELECT * FROM products WHERE id=?');
    $adjustStatement->execute([$adjustId]);
    $adjustProduct = $adjustStatement->fetch() ?: null;
}
$inventoryActivity = $db->query(<<<'SQL'
SELECT ie.*, p.name AS product_name, p.image, o.order_number, u.name AS administrator
FROM inventory_events ie
JOIN products p ON p.id=ie.product_id
LEFT JOIN orders o ON o.id=ie.order_id
LEFT JOIN users u ON u.id=ie.created_by
ORDER BY ie.id DESC
LIMIT 18
SQL)->fetchAll();
?>

<div class="admin-section-title inventory-heading">
    <div><span class="section-kicker">Stock operations</span><h2>Inventory control</h2><p>Live availability, stock value and a permanent adjustment trail.</p></div>
    <a class="admin-button admin-button--primary" href="?view=products&edit=new"><i class="ph ph-plus"></i> Add product</a>
</div>

<div class="inventory-metrics">
    <article><i class="ph ph-cube"></i><span>Units available</span><strong><?= number_format((int) ($inventoryMetrics['units'] ?? 0)) ?></strong></article>
    <article><i class="ph ph-currency-inr"></i><span>Retail stock value</span><strong>₹<?= number_format((int) ($inventoryMetrics['value'] ?? 0)) ?></strong></article>
    <article class="<?= (int) ($inventoryMetrics['low_count'] ?? 0) > 0 ? 'is-warning' : '' ?>"><i class="ph ph-gauge"></i><span>Low stock</span><strong><?= (int) ($inventoryMetrics['low_count'] ?? 0) ?></strong></article>
    <article class="<?= (int) ($inventoryMetrics['out_count'] ?? 0) > 0 ? 'is-danger' : '' ?>"><i class="ph ph-prohibit"></i><span>Out of stock</span><strong><?= (int) ($inventoryMetrics['out_count'] ?? 0) ?></strong></article>
</div>

<?php if ($adjustProduct): ?>
    <section class="admin-card stock-adjust-card"><div class="admin-card__header"><div class="stock-adjust-product"><img src="../<?= htmlspecialchars($adjustProduct['image']) ?>" alt=""><span><span class="section-kicker">Inventory adjustment</span><h2><?= htmlspecialchars($adjustProduct['name']) ?></h2><p><?= (int) $adjustProduct['stock'] ?> units currently available</p></span></div><a class="admin-action-icon" href="?view=inventory"><i class="ph ph-x"></i></a></div><div class="admin-card__body"><form method="post" class="admin-form stock-adjust-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="adjust_stock"><input type="hidden" name="product_id" value="<?= htmlspecialchars($adjustProduct['id']) ?>"><label><span>Adjustment</span><div class="number-input-prefix"><b>+/−</b><input type="number" name="adjustment" required min="-100000" max="100000" placeholder="Example: 24 or -2"></div><small>Use a positive number for received stock and a negative number for corrections.</small></label><label><span>Audit reason</span><input name="reason" required minlength="3" maxlength="240" placeholder="Example: Supplier delivery INV-2048"></label><button class="admin-button admin-button--primary" type="submit"><i class="ph ph-check"></i> Apply adjustment</button></form></div></section>
<?php endif; ?>

<div class="inventory-layout">
    <section class="admin-card inventory-table-card">
        <form method="get" class="order-filter-bar"><input type="hidden" name="view" value="inventory"><label class="order-filter-search"><i class="ph ph-magnifying-glass"></i><input name="q" value="<?= htmlspecialchars($inventoryQuery) ?>" placeholder="Search product, category or SKU"></label><select name="stock"><option value="">All stock states</option><option value="low" <?= $inventoryStatus === 'low' ? 'selected' : '' ?>>Low stock</option><option value="out" <?= $inventoryStatus === 'out' ? 'selected' : '' ?>>Out of stock</option><option value="healthy" <?= $inventoryStatus === 'healthy' ? 'selected' : '' ?>>Healthy</option><option value="hidden" <?= $inventoryStatus === 'hidden' ? 'selected' : '' ?>>Hidden products</option></select><button class="admin-button admin-button--primary">Filter</button><?php if ($inventoryQuery !== '' || $inventoryStatus !== ''): ?><a class="admin-button admin-button--ghost" href="?view=inventory">Clear</a><?php endif; ?></form>
        <?php if ($inventoryProducts): ?><div class="admin-table-wrap"><table class="admin-table inventory-table"><thead><tr><th>Product</th><th>SKU</th><th>Availability</th><th>Retail value</th><th>Updated</th><th></th></tr></thead><tbody><?php foreach ($inventoryProducts as $product): $stock = (int) $product['stock']; $stockClass = $stock === 0 ? 'is-out' : ($stock <= $lowStockThreshold ? 'is-low' : 'is-good'); ?><tr><td><div class="admin-table__product"><img src="../<?= htmlspecialchars($product['image']) ?>" alt=""><div><strong><?= htmlspecialchars($product['name']) ?></strong><span><?= htmlspecialchars($product['category'] . ' · ' . $product['weight']) ?></span></div></div></td><td><code><?= htmlspecialchars($product['sku'] ?: $product['id']) ?></code></td><td><div class="stock-level <?= $stockClass ?>"><strong><?= $stock ?></strong><span><?= $stock === 0 ? 'Out of stock' : ($stock <= $lowStockThreshold ? 'Low stock' : 'In stock') ?></span></div></td><td><strong>₹<?= number_format($stock * (int) $product['price']) ?></strong></td><td><span><?= htmlspecialchars(date('j M Y', strtotime((string) $product['updated_at']))) ?></span></td><td><div class="admin-actions"><a class="admin-button admin-button--secondary" href="?view=inventory&adjust=<?= rawurlencode($product['id']) ?>"><i class="ph ph-plus-minus"></i> Adjust</a><a class="admin-action-icon" href="?view=products&edit=<?= rawurlencode($product['id']) ?>" title="Edit product"><i class="ph ph-pencil-simple"></i></a></div></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><i class="ph ph-magnifying-glass"></i><h3>No products match</h3><p>Clear the inventory filters to see the full catalogue.</p></div><?php endif; ?>
    </section>

    <aside class="admin-card inventory-activity"><div class="admin-card__header"><div><span class="section-kicker">Audit trail</span><h2>Recent movement</h2><p>Orders and manual corrections</p></div></div><?php if ($inventoryActivity): ?><div class="inventory-activity-list"><?php foreach ($inventoryActivity as $event): ?><article><img src="../<?= htmlspecialchars($event['image']) ?>" alt=""><span><strong><?= htmlspecialchars($event['product_name']) ?></strong><small><?= htmlspecialchars($event['reason']) ?><?= $event['order_number'] ? ' · ' . htmlspecialchars($event['order_number']) : '' ?></small><time><?= htmlspecialchars(date('j M, g:i a', strtotime((string) $event['created_at']))) ?></time></span><b class="<?= (int) $event['adjustment'] > 0 ? 'is-positive' : 'is-negative' ?>"><?= (int) $event['adjustment'] > 0 ? '+' : '' ?><?= (int) $event['adjustment'] ?></b></article><?php endforeach; ?></div><?php else: ?><div class="empty-state"><i class="ph ph-clock-counter-clockwise"></i><h3>No movement yet</h3><p>New orders and manual stock changes will create an audit trail here.</p></div><?php endif; ?></aside>
</div>
