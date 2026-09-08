<?php
declare(strict_types=1);
require __DIR__ . '/../includes/platform.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    gawdee_json_response(['ok'=>false,'message'=>'Method not allowed.'],405);
}
$products = array_map(static fn($p)=>[
    'id'=>$p['id'],'name'=>$p['full_name'],'price'=>(int)$p['price'],
    'image'=>$p['image'],'stock'=>(int)$p['stock'],
],gawdee_products());
gawdee_json_response(['ok'=>true,'products'=>$products]);
