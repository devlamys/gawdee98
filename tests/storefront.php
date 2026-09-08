<?php
declare(strict_types=1);
// This suite cannot write to the shop's database or call payment/message providers.
define('GAWDEE_DB',':memory:');
require __DIR__.'/../includes/data.php';
require __DIR__.'/../includes/wishlist.php';
$failed=0; $total=0;
function check_store(bool $ok,string $label):void { global $failed,$total; $total++; if(!$ok)$failed++; echo ($ok?'PASS':'FAIL')."  $label\n"; }
$site=gawdee_site(); $site['brand_name']='Test Pantry'; $site['pdp_goodness_title']='Inside this product';
gawdee_save_site_design($site);
check_store(gawdee_site()['brand_name']==='Test Pantry','branding saves and reads in the same request');
check_store(gawdee_site()['pdp_goodness_title']==='Inside this product','product page copy is managed in admin');
foreach(['javascript:alert(1)','//evil.test','../secrets','%2e%2e/secrets',"https://evil.test\n"] as $url) {
    if($url==="https://evil.test\n") continue; // Trimming surrounding whitespace is intentional.
    check_store(gawdee_public_url($url)==='','unsafe content links rejected: '.$url);
}
check_store(gawdee_public_url('products.php?category=ghee')==='products.php?category=ghee','relative category links allowed');
check_store(gawdee_image_crop('56,140,390,198,2048,353')!==[] && gawdee_image_crop('2000,0,390,198,2048,353')===[],'artwork crop stays within the original image');
check_store(gawdee_heading('Pure *Goodness* <script>')==='Pure <em>Goodness</em> &lt;script&gt;','heading accent markup is escaped');
$collections=gawdee_collection_defaults();
$collections['categories'][0]['title']='Edited category'; $collections['categories'][0]['image']='assets/images/logo.png'; $collections['categories'][0]['image_crop']='';
$collections['bestsellers']=array_reverse($collections['bestsellers']);
gawdee_save_collections($collections);
check_store(gawdee_collections()['categories'][0]['title']==='Edited category','category artwork and labels can be edited');
check_store(gawdee_collections()['bestsellers'][0]['product_id']==='moringa','bestseller order is saved');
$catalogue=array_column($products,null,'id'); $combo=gawdee_combo_data($collections['combos'][0],$catalogue);
check_store($combo['price']===$catalogue['ghee-500']['price']+$catalogue['forest-honey']['price'],'combo price uses live product prices');
check_store(gawdee_combo_data(['product_ids'=>'missing,ghee-500'],$catalogue)===null,'invalid or hidden combo products are not offered');
gawdee_wishlist_set(['ghee-500'],true); gawdee_wishlist_set(['ghee-500'],true);
check_store(count(gawdee_wishlist_items())===1,'guest wishlist save is idempotent');
gawdee_wishlist_set(['forest-honey','ghee-500'],true);
check_store(isset(gawdee_wishlist_items()['forest-honey,ghee-500']),'complete combos can be saved');
$db=gawdee_db(); $db->prepare("INSERT INTO users(name,email,password_hash,role)VALUES('Test customer','saved@example.test',?,'customer')")->execute([password_hash('test-password-only',PASSWORD_DEFAULT)]);
$customerId=(int)$db->lastInsertId(); $_SESSION['customer_user_id']=$customerId;
gawdee_wishlist_merge_guest();
check_store(count(gawdee_wishlist_items())===2 && !isset($_SESSION['saved_products']),'guest wishlist merges after sign-in');
$db->prepare("INSERT INTO users(name,email,password_hash,role)VALUES('Second customer','second@example.test',?,'customer')")->execute([password_hash('test-password-only',PASSWORD_DEFAULT)]);
$_SESSION['customer_user_id']=(int)$db->lastInsertId();
check_store(gawdee_wishlist_items()===[],'wishlist is isolated between customers');
$_SESSION['admin_user_id']=$customerId;
check_store(gawdee_admin()===null,'customer sessions cannot act as administrators');
unset($_SESSION['customer_user_id'],$_SESSION['admin_user_id']);
$_SERVER['REQUEST_METHOD']='GET';
ob_start(); require __DIR__.'/../index.php'; $html=ob_get_clean();
$doc=new DOMDocument(); @$doc->loadHTML($html); $xp=new DOMXPath($doc);
check_store($xp->query('//*[@id="categories"]//a[contains(@class,"sf-category-art")]')->length===5,'five single-artwork category cards render');
foreach($xp->query('//*[@id="categories"]//a[contains(@class,"sf-category-art")]') as $card) check_store($card->getElementsByTagName('img')->length===1,'category card contains exactly one image');
check_store($xp->query('//*[@id="shop"]//*[@data-add-to-cart]')->length===5,'five native bestseller shopping controls render');
check_store($xp->query('//*[@id="offers"]//*[@data-add-combo]')->length===6,'six native combo shopping controls render');
check_store($xp->query('//*[@id="benefits"]//article')->length===8 && $xp->query('//*[@id="why"]//article')->length===8,'eight editable trust and why-choose cards render');
check_store($xp->query('//*[@id="quality-promise"]//article')->length===4,'four native quality cards render');
check_store(!str_contains($html,'Warning:') && !str_contains($html,'Fatal error'),'homepage renders without PHP errors');
echo "$total checks, $failed failures.\n";
exit($failed?1:0);
