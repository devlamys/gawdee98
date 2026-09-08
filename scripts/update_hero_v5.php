<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/platform.php';

$db = gawdee_db();
$banners = [
    [
        'assets/images/hero-slide-independence-v5.webp',
        'assets/images/hero-slide-independence-mobile-v5.webp',
        'Happy Independence Day. Flat 10% off with code FREEDOM10. Featuring exact Gawdee A2 Gir Cow Ghee, Burra Sugar and MixMe Choco packs.',
        'Independence Day wellness offer',
    ],
    [
        'assets/images/hero-slide-ghee-v5.webp',
        'assets/images/hero-slide-ghee-mobile-v5.webp',
        'Freedom to choose pure tradition with Gawdee Bilona-crafted A2 Gir Cow Ghee.',
        'Traditional A2 Ghee',
    ],
    [
        'assets/images/hero-slide-mixme-v5.webp',
        'assets/images/hero-slide-mixme-mobile-v5.webp',
        'Celebrate everyday wellness with Gawdee MixMe Choco.',
        'MixMe daily nutrition',
    ],
];

$update = $db->prepare(
    'UPDATE banners SET desktop_image = ?, mobile_image = ?, alt_text = ? WHERE title = ?'
);

$db->beginTransaction();
foreach ($banners as $banner) {
    $update->execute($banner);
}
$db->commit();

echo 'Updated ' . count($banners) . " hero banners.\n";
