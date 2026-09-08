<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';

$view = in_array($argv[1] ?? '', ['dashboard', 'orders', 'inventory', 'customers', 'integrations'], true) ? (string) $argv[1] : 'dashboard';
$markers = [
    'dashboard' => 'Live commerce overview',
    'orders' => 'Orders & fulfilment',
    'inventory' => 'Inventory control',
    'customers' => 'Customer intelligence',
    'integrations' => 'Payments, delivery & messaging',
];

$db = gawdee_db();
$db->beginTransaction();
try {
    $email = 'operations-render-' . bin2hex(random_bytes(4)) . '@example.test';
    $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES ('Operations Render', ?, ?, 'admin')")
        ->execute([$email, password_hash('temporary-password', PASSWORD_DEFAULT)]);
    $_SESSION['admin_user_id'] = (int) $db->lastInsertId();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['view' => $view];
    if ($view === 'orders' && ($argv[2] ?? '') === 'new') {
        $_GET['new'] = '1';
        $markers['orders'] = 'Create a phone or counter order';
    }
    ob_start();
    require __DIR__ . '/../admin/index.php';
    $html = (string) ob_get_clean();
    $passed = str_contains($html, $markers[$view])
        && str_contains($html, 'Command centre')
        && str_contains($html, 'admin-modern.css')
        && str_contains($html, 'data-admin-command-search')
        && str_contains($html, 'admin-user-chip')
        && ($view !== 'dashboard' || str_contains($html, 'dashboard-quick-actions'))
        && !str_contains($html, 'Fatal error');
    echo ($passed ? 'PASS' : 'FAIL') . '  admin ' . $view . ' operations view renders' . PHP_EOL;
    $exitCode = $passed ? 0 : 1;
} finally {
    unset($_SESSION['admin_user_id']);
    $db->rollBack();
}

exit($exitCode);
