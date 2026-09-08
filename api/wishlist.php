<?php
declare(strict_types=1);
require __DIR__ . '/../includes/wishlist.php';
try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'POST') {
        $payload = gawdee_request_json();
        gawdee_verify_csrf($payload['csrf_token'] ?? null);
        if (!is_array($payload['ids'] ?? null) || !is_bool($payload['saved'] ?? null)) throw new InvalidArgumentException('Invalid wishlist request.');
        $items = gawdee_wishlist_set($payload['ids'], $payload['saved']);
    } elseif ($method === 'GET') {
        $items = gawdee_wishlist_items();
    } else {
        header('Allow: GET, POST');
        gawdee_json_response(['ok'=>false,'message'=>'Method not allowed.'],405);
    }
    gawdee_json_response(['ok'=>true,'items'=>(object)$items,'count'=>count($items)]);
} catch (InvalidArgumentException $e) {
    gawdee_json_response(['ok'=>false,'message'=>$e->getMessage()],422);
} catch (RuntimeException $e) {
    gawdee_json_response(['ok'=>false,'message'=>'Your session expired or the wishlist is unavailable. Refresh and try again.'],400);
}
