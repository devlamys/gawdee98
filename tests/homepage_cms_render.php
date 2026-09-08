<?php
declare(strict_types=1);
if(!defined('GAWDEE_DB')) define('GAWDEE_DB',':memory:');
require_once __DIR__.'/../includes/platform.php';
$db=gawdee_db();
$db->prepare('UPDATE cms_sections SET title=?,sort_order=? WHERE section_key=?')->execute(['Edited Bestseller Heading',11,'shop']);
$db->prepare('UPDATE cms_sections SET is_active=0 WHERE section_key=?')->execute(['why']);
$db->prepare('INSERT INTO video_testimonials(name,quote,rating,video_type,external_url,is_active)VALUES(?,?,?,?,?,1)')->execute(['Render Customer','Temporary local QA only',5,'external_video','https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
$_SERVER['REQUEST_METHOD']='GET';
ob_start(); require __DIR__.'/../index.php'; $html=ob_get_clean();
$checks=[
    'editable section title reaches storefront'=>str_contains($html,'Edited Bestseller Heading'),
    'section order reaches storefront'=>strpos($html,'id="shop"')<strpos($html,'id="benefits"'),
    'hidden sections do not render'=>!str_contains($html,'id="why"'),
    'hero remains an image with no separate headline overlay'=>str_contains($html,'gawdee-reference-poster-hero-v3.png')&&!str_contains($html,'gawdee-hero__copy'),
    'native bestseller controls render'=>substr_count($html,'data-add-to-cart')===5,
    'six complete combos render'=>substr_count($html,'data-add-combo')===6,
    'safe video embed renders'=>str_contains($html,'https://www.youtube.com/embed/dQw4w9WgXcQ'),
    'no PHP warnings'=>!str_contains($html,'Warning:')&&!str_contains($html,'Fatal error'),
];
$failures=0; foreach($checks as $label=>$passed) { echo ($passed?'PASS':'FAIL')." $label\n"; if(!$passed)$failures++; }
exit($failures?1:0);
