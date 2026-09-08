<?php
declare(strict_types=1);
if(!defined('GAWDEE_DB')) define('GAWDEE_DB',':memory:');
$testCategory=$argv[1]??'all';
$expected=['all'=>8,'ghee'=>1,'honey'=>1,'nutrition'=>2,'sugar'=>2,'wellness'=>2,'grains'=>0,'combos'=>6];
if(!isset($expected[$testCategory])) throw new InvalidArgumentException('Unknown category');
$_SERVER['REQUEST_METHOD']='GET'; $_GET=['category'=>$testCategory];
ob_start(); require __DIR__.'/../products.php'; $html=ob_get_clean();
$doc=new DOMDocument(); @$doc->loadHTML($html); $xp=new DOMXPath($doc);
$checks=[
    'category heading and current tab render'=>str_contains($html,'aria-current="page"')&&str_contains($html,'category-catalog-hero'),
    'expected native shopping cards render'=>$xp->query($testCategory==='combos'?'//*[@data-add-combo]':'//*[@data-add-to-cart]')->length===$expected[$testCategory],
    'search and sorting controls render'=>$xp->query('//input[@name="search"]')->length===1&&$xp->query('//select[@name="sort"]')->length===1,
    'stock filter renders'=>$xp->query('//input[@name="stock"]')->length===1,
    'shared footer renders'=>str_contains($html,'id="site-footer"'),
    'response has no PHP errors'=>!str_contains($html,'Warning:')&&!str_contains($html,'Fatal error'),
];
if(!in_array($testCategory,['combos','grains'],true))$checks['product photos have distinct hover images']=$xp->query('//img[contains(@class,"product-image-swap__secondary")]')->length===$expected[$testCategory];
$failures=0;
foreach($checks as $label=>$passed) { echo ($passed?'PASS':'FAIL')." [$testCategory] $label\n"; if(!$passed)$failures++; }
exit($failures?1:0);
