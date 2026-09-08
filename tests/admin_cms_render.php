<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';

$view = in_array($argv[1] ?? '', ['cms', 'section-items', 'testimonials', 'video-testimonials', 'reviews', 'media', 'blog','storefront','site-design','products','product-create'], true) ? (string) $argv[1] : 'cms';
$markers = [
    'storefront'=>'Storefront collections',
    'site-design'=>'Brand &amp; appearance',
    'products'=>'Product catalogue',
    'product-create'=>'Create product',
    'cms' => 'Homepage content studio',
    'section-items' => 'Homepage section item manager',
    'testimonials' => 'Customer story manager',
    'video-testimonials' => 'Video testimonial manager',
    'reviews' => 'Product review manager',
    'media' => 'Homepage media library',
    'blog' => 'Stories & publishing',
];

$db = gawdee_db();
$db->beginTransaction();
try {
    $email = 'cms-render-' . bin2hex(random_bytes(4)) . '@example.test';
    $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES ('CMS Render', ?, ?, 'admin')")->execute([$email, password_hash('temporary-password', PASSWORD_DEFAULT)]);
    $_SESSION['admin_user_id'] = (int) $db->lastInsertId();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $testedView = $view;
    $_GET = $view === 'product-create' ? ['view'=>'products','edit'=>'new'] : ['view' => $view];
    ob_start();
    require __DIR__ . '/../admin/index.php';
    $html = (string) ob_get_clean();
    $passed = str_contains($html, $markers[$testedView]) && str_contains($html, 'Homepage CMS') && !str_contains($html, 'Fatal error') && !str_contains($html,'Warning:');
    echo ($passed ? 'PASS' : 'FAIL') . '  admin ' . $testedView . ' view renders' . PHP_EOL;
    $exitCode = $passed ? 0 : 1;
} finally {
    unset($_SESSION['admin_user_id']);
    $db->rollBack();
}

exit($exitCode);
