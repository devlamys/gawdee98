<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/platform.php';

const GAWDEE_CATALOG_API = 'https://gawdeebackend.grafizen.in/api/v2';

function catalog_json(string $path): array
{
    $curl = curl_init(GAWDEE_CATALOG_API . $path);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_USERAGENT => 'Gawdee catalogue importer/1.0',
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if (!is_string($body) || $status !== 200) {
        throw new RuntimeException('Catalogue request failed (' . $status . '): ' . ($error ?: $path));
    }
    $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    return is_array($decoded) ? $decoded : [];
}

function catalog_asset(string $url, string $slug, string $filename): ?string
{
    if (!preg_match('#^https://(?:incimage|storage)\.server\.grafizen\.in/#i', $url)) {
        return null;
    }
    $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!in_array($extension, ['webp', 'png', 'jpg', 'jpeg', 'gif', 'avif'], true)) {
        $extension = 'webp';
    }
    $directory = GAWDEE_ROOT . '/assets/images/catalog/' . $slug;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create image directory for ' . $slug);
    }
    $relative = 'assets/images/catalog/' . $slug . '/' . $filename . '.' . $extension;
    $absolute = GAWDEE_ROOT . '/' . $relative;
    if (is_file($absolute) && filesize($absolute) > 500) {
        return $relative;
    }
    $temporary = $absolute . '.part';
    $handle = fopen($temporary, 'wb');
    if (!$handle) {
        return null;
    }
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_FILE => $handle,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Gawdee catalogue importer/1.0',
        CURLOPT_FAILONERROR => true,
    ]);
    $ok = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    fclose($handle);
    if (!$ok || $status < 200 || $status >= 300 || !is_file($temporary) || filesize($temporary) < 500) {
        @unlink($temporary);
        return null;
    }
    rename($temporary, $absolute);
    return $relative;
}

$categoryResponse = catalog_json('/admin/categories');
$categoryNames = [];
foreach (($categoryResponse['category'] ?? []) as $category) {
    if (is_array($category) && isset($category['_id'], $category['name'])) {
        $categoryNames[(string) $category['_id']] = (string) $category['name'];
    }
}

$catalogResponse = catalog_json('/products');
$catalogProducts = $catalogResponse['data']['products'] ?? [];
if (!is_array($catalogProducts) || !$catalogProducts) {
    throw new RuntimeException('The official catalogue returned no products.');
}

$categoryConfig = [
    'Ghee' => ['Ghee', 'ghee', 'Bilona method', '#d8a934'],
    'Honey' => ['Honey', 'honey', 'Raw & natural', '#ad6f2d'],
    'Drops' => ['Wellness', 'wellness', 'Traditional care', '#8a663e'],
    'Powder' => ['Wellness', 'wellness', 'Plant-led', '#5e7e3e'],
    'Mix Me' => ['Nutrition', 'nutrition', 'Daily nutrition', '#7fa143'],
    'Sugar' => ['Natural sugar', 'sugar', 'Pantry staple', '#9a7245'],
];

$knownIds = [
    'gawdee-mixme-choco-500-g' => 'mixme-choco',
    'gawdee-mixme-elaichi-500-g' => 'mixme-elaichi',
    'gawdee-gir-cow-a2-ghee-500-ml' => 'ghee-500',
    'gawdee-raw-wild-forest-honey-650-g' => 'forest-honey',
    'gawdee-taral-drop-30-ml' => 'taral-drop',
    'gawdee-moringa-powder-300-g' => 'moringa',
    'gawdee-bura-sugar-1-kg' => 'burra-sugar',
    'gawdee-white-sugar-1kg' => 'white-sugar',
];

$db = gawdee_db();
$existingBySlug = $db->prepare('SELECT id FROM products WHERE slug = ?');
$upsert = $db->prepare(<<<'SQL'
INSERT INTO products
(id, slug, name, full_name, category, category_key, tag, price, original_price, weight, image, description, accent, stock, stock_status, sku, source_id, source_url, rating, review_count, gallery_json, details_json, is_active, updated_at)
VALUES
(:id, :slug, :name, :full_name, :category, :category_key, :tag, :price, :original_price, :weight, :image, :description, :accent, :stock, :stock_status, :sku, :source_id, :source_url, :rating, :review_count, :gallery_json, :details_json, :is_active, CURRENT_TIMESTAMP)
ON CONFLICT(slug) DO UPDATE SET
name=excluded.name, full_name=excluded.full_name, category=excluded.category, category_key=excluded.category_key,
tag=excluded.tag, price=excluded.price, original_price=excluded.original_price, weight=excluded.weight,
image=excluded.image, description=excluded.description, accent=excluded.accent, stock=excluded.stock,
stock_status=excluded.stock_status, sku=excluded.sku, source_id=excluded.source_id, source_url=excluded.source_url,
rating=excluded.rating, review_count=excluded.review_count, gallery_json=excluded.gallery_json,
details_json=excluded.details_json, is_active=excluded.is_active, updated_at=CURRENT_TIMESTAMP
SQL);

$imported = 0;
$downloadedImages = 0;
foreach ($catalogProducts as $summary) {
    if (!is_array($summary) || empty($summary['slug']) || empty($summary['isActive'])) {
        continue;
    }
    $slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower((string) $summary['slug'])) ?: '';
    if ($slug === '') {
        continue;
    }
    $detailResponse = catalog_json('/product/by-slug/' . rawurlencode($slug));
    $detail = is_array($detailResponse['data'] ?? null) ? $detailResponse['data'] : $summary;
    $categoryValue = $detail['categoryId'] ?? $summary['categoryId'] ?? '';
    $categoryId = is_array($categoryValue) ? (string) ($categoryValue['_id'] ?? '') : (string) $categoryValue;
    $sourceCategory = is_array($categoryValue) ? (string) ($categoryValue['name'] ?? '') : ($categoryNames[$categoryId] ?? 'Wellness');
    [$category, $categoryKey, $tag, $accent] = $categoryConfig[$sourceCategory] ?? ['Wellness', 'wellness', 'Natural essential', '#0d7868'];

    $mainUrls = [];
    foreach (($detail['images'] ?? []) as $url) {
        if (is_string($url) && $url !== '') {
            $mainUrls[$url] = $url;
        }
    }
    if (!$mainUrls && !empty($detail['featuredImage'])) {
        $mainUrls[(string) $detail['featuredImage']] = (string) $detail['featuredImage'];
    }
    $gallery = [];
    $imageIndex = 1;
    foreach ($mainUrls as $url) {
        $local = catalog_asset($url, $slug, sprintf('gallery-%02d', $imageIndex));
        if ($local) {
            $gallery[] = ['src' => $local, 'label' => $imageIndex === 1 ? 'Product view' : 'Product detail ' . $imageIndex];
            $downloadedImages++;
        }
        $imageIndex++;
    }

    $aPlusImages = [];
    foreach (($detail['aPlusContent']['images'] ?? []) as $index => $imageRecord) {
        $url = is_array($imageRecord) ? (string) ($imageRecord['image'] ?? $imageRecord['url'] ?? '') : '';
        if ($url === '') {
            continue;
        }
        $local = catalog_asset($url, $slug, sprintf('story-%02d', $index + 1));
        if ($local) {
            $aPlusImages[] = [
                'src' => $local,
                'alt' => (string) ($imageRecord['alt'] ?? $detail['name'] ?? 'Gawdee product'),
                'title' => (string) ($imageRecord['title'] ?? 'Product story'),
            ];
            $downloadedImages++;
        }
    }
    $detail['_local'] = ['gallery' => $gallery, 'aplus_images' => $aPlusImages, 'imported_at' => date(DATE_ATOM)];

    $existingBySlug->execute([$slug]);
    $existingId = $existingBySlug->fetchColumn();
    $productId = is_string($existingId) && $existingId !== '' ? $existingId : ($knownIds[$slug] ?? $slug);
    $fullName = trim((string) ($detail['name'] ?? $summary['name'] ?? 'Gawdee product'));
    $shortName = trim((string) preg_replace('/^Gawdee\s+/i', '', $fullName));
    $weight = trim((string) ($detail['displayWeight'] ?? ''));
    if ($weight === '') {
        $weight = trim((string) ($detail['weight'] ?? '') . ' ' . (string) ($detail['weightUnit'] ?? ''));
    }
    $description = trim((string) ($detail['content'] ?? '')) ?: trim((string) ($detail['description'] ?? ''));
    $primaryImage = $gallery[0]['src'] ?? (string) ($detail['featuredImage'] ?? 'assets/images/hero-product-collage-v2.png');
    $stockStatus = (string) ($detail['stockStatus'] ?? 'in_stock');

    $upsert->execute([
        'id' => $productId,
        'slug' => $slug,
        'name' => $shortName,
        'full_name' => $fullName,
        'category' => $category,
        'category_key' => $categoryKey,
        'tag' => $tag,
        'price' => (int) ($detail['salePrice'] ?? $detail['price'] ?? 0),
        'original_price' => (int) ($detail['originalPrice'] ?? $detail['price'] ?? 0),
        'weight' => $weight,
        'image' => $primaryImage,
        'description' => $description,
        'accent' => $accent,
        'stock' => $stockStatus === 'in_stock' ? 100 : 0,
        'stock_status' => $stockStatus,
        'sku' => (string) ($detail['sku'] ?? ''),
        'source_id' => (string) ($detail['_id'] ?? ''),
        'source_url' => 'https://gawdee.com/product/' . $slug,
        'rating' => (float) ($detail['rating'] ?? 0),
        'review_count' => (int) ($detail['reviewCount'] ?? 0),
        'gallery_json' => json_encode($gallery, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'details_json' => json_encode($detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'is_active' => 1,
    ]);
    $imported++;
    echo 'Imported ' . $slug . ' (' . count($gallery) . ' gallery, ' . count($aPlusImages) . ' story images)' . PHP_EOL;
}

echo 'Complete: ' . $imported . ' products and ' . $downloadedImages . ' image references processed.' . PHP_EOL;
