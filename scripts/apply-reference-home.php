<?php
/** One-time, reversible content update for the supplied September homepage references. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../includes/data.php';
$db = gawdee_db();
if (gawdee_setting('reference_home_20260908_applied','') === '1') { echo "Reference content already applied; admin edits preserved.\n"; exit; }
$db->beginTransaction();
try {
    gawdee_set_setting('reference_home_20260908_backup',json_encode([
        'sections'=>$db->query('SELECT * FROM cms_sections')->fetchAll(),
        'collections'=>gawdee_setting('storefront_collections_v1','{}'),
    ],JSON_THROW_ON_ERROR));
    $sections = [
        'benefits'=>['Our promises','Pure nutrition, made simply','','','',15],
        'categories'=>['Shop by','Category','','View All','products.php',20],
        'shop'=>['Our Bestsellers','Loved by *Thousands*','Pure nutrition. Real results. Customer favourites, handpicked for you.','View All Products','products.php',30],
        'why'=>['Why Choose Us','Pure Goodness, Real Trust','From our farms to your home, we bring nature’s best.','','',40],
        'combos'=>['Special Combo Offers','Better Combos *for a Healthier You*','Pure products. Perfect combinations. More value for your family.','View All Combos','products.php?category=combos',50],
        'assurance'=>['Our Promise','Only Perfection *Makes The Cut*','From sourcing to your table, every product goes through a rigorous process to ensure purity, safety and uncompromised nutrition.','Explore Our Products','products.php',60],
    ];
    $update = $db->prepare('UPDATE cms_sections SET eyebrow=?,title=?,subtitle=?,button_label=?,button_url=?,sort_order=?,updated_at=CURRENT_TIMESTAMP WHERE section_key=?');
    foreach($sections as $key=>$values) $update->execute([...$values,$key]);
    $db->exec("UPDATE cms_sections SET sort_order=70 WHERE section_key='about'");
    $db->exec("UPDATE cms_sections SET sort_order=75 WHERE section_key='process'");
    // Retire the dated sample promotion without deleting its content or artwork.
    $db->exec("UPDATE cms_sections SET is_active=0 WHERE section_key='offer' AND eyebrow='Independence Day offer'");
    $collections = gawdee_collections();
    $defaults = gawdee_collection_defaults();
    foreach(['categories','navigation','quality','trust','why_choose','header_benefits','bestseller_benefits','combo_benefits','quality_benefits'] as $key) $collections[$key]=$defaults[$key];
    foreach($collections['bestsellers'] as &$item) foreach($defaults['bestsellers'] as $default) if($item['product_id']===$default['product_id']) $item=array_replace($default,$item);
    unset($item);
    gawdee_set_setting('storefront_collections_v1',json_encode($collections,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES));
    gawdee_set_setting('reference_home_20260908_applied','1');
    $db->commit();
    echo "Reference sections applied. Previous content is preserved in reference_home_20260908_backup.\n";
} catch(Throwable $e) { $db->rollBack(); throw $e; }
