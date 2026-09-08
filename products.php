<?php
declare(strict_types=1);
require __DIR__ . '/includes/data.php';
$site = gawdee_site();
$collections = gawdee_collections();
$categoryFilters = ['all'=>['title'=>'All Products','subtitle'=>$site['catalogue_intro'],'icon'=>'ph-squares-four','image'=>$site['catalogue_image']]];
foreach ($collections['categories'] as $category) {
    parse_str((string)(parse_url($category['url'],PHP_URL_QUERY) ?? ''),$query);
    $key = (string)($query['category'] ?? '');
    if ($key && $key !== 'all') $categoryFilters[$key] = $category;
}
foreach ($products as $product) {
    $key = $product['category_key'];
    if (!isset($categoryFilters[$key])) $categoryFilters[$key] = ['title'=>$product['category'],'subtitle'=>'Explore our '.$product['category'].' collection.','icon'=>'ph-leaf','image'=>$product['image']];
}
$categoryFilters['combos'] = ['title'=>'Combo Deals','subtitle'=>'Thoughtful combinations. All your favourites in one easy selection.','icon'=>'ph-gift','image'=>$collections['combos'][0]['image']??$site['catalogue_image']];
$requestedCategory = (string)($_GET['category'] ?? 'all');
$activeCategory = isset($categoryFilters[$requestedCategory]) ? $requestedCategory : 'all';
$headerSearchValue = mb_substr(trim((string)($_GET['search'] ?? '')),0,160);
$sortLabels = ['featured'=>'Featured','price-low'=>'Price: low to high','price-high'=>'Price: high to low','name'=>'Name: A–Z','newest'=>'Newest first'];
$sort = isset($sortLabels[$_GET['sort'] ?? '']) ? (string)$_GET['sort'] : 'featured';
$stockOnly = ($_GET['stock'] ?? '') === '1';
$pageHero = $categoryFilters[$activeCategory];
if(!empty($pageHero['image_crop'])) foreach($products as $candidate) if($candidate['category_key']===$activeCategory) { $pageHero['image']=$candidate['image']; $pageHero['image_crop']=''; break; }
$pageTitle = $pageHero['title'].' | '.$site['brand_name'];
$pageDescription = $pageHero['subtitle'];
$bodyClass = 'catalog-page catalog-page--reference';
$filtered = array_values(array_filter($products,static fn($p) =>
    ($activeCategory === 'all' || $p['category_key'] === $activeCategory) &&
    (!$stockOnly || $p['stock'] > 0) &&
    ($headerSearchValue === '' || mb_stripos($p['full_name'].' '.$p['category'].' '.$p['weight'],$headerSearchValue) !== false)
));
switch($sort) {
    case 'price-low': usort($filtered,static fn($a,$b)=>$a['price']<=>$b['price']); break;
    case 'price-high': usort($filtered,static fn($a,$b)=>$b['price']<=>$a['price']); break;
    case 'name': usort($filtered,static fn($a,$b)=>strnatcasecmp($a['name'],$b['name'])); break;
    case 'newest': usort($filtered,static fn($a,$b)=>strcmp($b['created_at'],$a['created_at'])); break;
}
$shelves = [];
foreach($filtered as $product) $shelves[$sort==='featured' && $activeCategory==='all' ? $product['category_key'] : $activeCategory][] = $product;
$combos = [];
if($activeCategory==='combos') {
    $catalogue = array_column($products,null,'id');
    foreach($collections['combos'] as $row) {
        $combo = gawdee_combo_data($row,$catalogue);
        if($combo && (!$stockOnly || $combo['stock']>0) && (!$headerSearchValue || mb_stripos($combo['title'].' '.implode(' ',array_column($combo['bundle'],'name')),$headerSearchValue)!==false)) $combos[]=$combo;
    }
    if($sort==='price-low') usort($combos,static fn($a,$b)=>$a['price']<=>$b['price']);
    if($sort==='price-high') usort($combos,static fn($a,$b)=>$b['price']<=>$a['price']);
    if($sort==='name') usort($combos,static fn($a,$b)=>strnatcasecmp($a['title'],$b['title']));
}
$resultCount = $activeCategory==='combos' ? count($combos) : count($filtered);
require __DIR__ . '/includes/header.php';
?>
<section class="category-catalog-hero">
<div class="category-catalog-hero__veil" aria-hidden="true"></div>
<div class="container category-catalog-hero__inner">
<div class="category-catalog-hero__copy"><span><?= htmlspecialchars($site['brand_tagline']) ?></span><h1><?= htmlspecialchars($activeCategory==='all' ? $site['catalogue_title'] : $pageHero['title']) ?></h1><p><?= htmlspecialchars($pageHero['subtitle']) ?></p><a href="#product-catalog" class="category-catalog-hero__button">Explore collection <i class="ph ph-arrow-right"></i></a></div>
<div class="category-catalog-hero__visual"><span class="category-catalog-hero__halo" aria-hidden="true"></span><?php gawdee_artwork($pageHero['image'],$pageHero['image_crop']??'',$pageHero['title']); ?></div>
</div></section>
<nav class="category-catalog-tabs" aria-label="Shop by category"><div class="container">
<?php foreach($categoryFilters as $key=>$category): ?><a href="products.php?category=<?= rawurlencode($key) ?>#product-catalog" class="<?= $key===$activeCategory?'is-active':'' ?>" <?= $key===$activeCategory?'aria-current="page"':'' ?>><span><i class="ph <?= htmlspecialchars($category['icon']) ?>"></i></span><strong><?= htmlspecialchars($category['title']) ?></strong></a><?php endforeach; ?>
</div></nav>
<section class="category-catalog" id="product-catalog"><div class="container">
<header class="category-catalog__toolbar"><div><span>Find your everyday favourites</span><h2><?= htmlspecialchars($pageHero['title']) ?></h2></div></header>
<form method="get" action="products.php#product-catalog" class="sf-catalog-controls" data-catalog-form>
<input type="hidden" name="category" value="<?= htmlspecialchars($activeCategory) ?>">
<label class="category-catalog__search"><i class="ph ph-magnifying-glass"></i><span class="sr-only">Search products</span><input type="search" name="search" value="<?= htmlspecialchars($headerSearchValue) ?>" placeholder="Search the collection" maxlength="160"></label>
<label>Sort by <select name="sort"><?php foreach($sortLabels as $value=>$label): ?><option value="<?= $value ?>" <?= $sort===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<label><input type="checkbox" name="stock" value="1" <?= $stockOnly?'checked':'' ?>> In stock only</label>
<button class="sf-button" type="submit">Apply</button>
<span role="status"><?= $resultCount ?> <?= $activeCategory==='combos'?'combos':($resultCount===1?'product':'products') ?></span>
<?php if($stockOnly || $headerSearchValue || $sort!=='featured'): ?><a class="sf-text-link" href="products.php?category=<?= rawurlencode($activeCategory) ?>#product-catalog">Clear filters</a><?php endif; ?>
</form>
<div class="category-catalog__shelves">
<?php if($activeCategory==='combos'): ?><div class="sf-combo-grid"><?php foreach($combos as $combo) gawdee_card_combo($combo); ?></div><?php endif; ?>
<?php foreach($shelves as $key=>$items): $category = $categoryFilters[$key]; ?>
<section class="category-product-shelf"><header><div><span><i class="ph <?= htmlspecialchars($category['icon']) ?>"></i></span><h2><?= htmlspecialchars($category['title']) ?></h2><p><?= htmlspecialchars($category['subtitle']) ?></p></div><?php if($key!=='all' && $activeCategory==='all'): ?><a href="products.php?category=<?= rawurlencode($key) ?>#product-catalog">View all <i class="ph ph-arrow-right"></i></a><?php endif; ?></header>
<div class="sf-product-grid"><?php foreach($items as $product) gawdee_card_product($product); ?></div>
</section>
<?php endforeach; ?>
</div>
<?php if(!$resultCount): ?><div class="sf-empty"><i class="ph ph-magnifying-glass"></i><h2>No products found</h2><p>Try another search or remove a filter.</p><a class="sf-button" href="products.php">Browse all products</a></div><?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
