<?php
declare(strict_types=1);
require_once __DIR__ . '/platform.php';

function gawdee_wishlist_ids(array $ids): array
{
    if (count($ids) < 1 || count($ids) > 6) throw new InvalidArgumentException('Choose between one and six products.');
    foreach ($ids as $id) if (!is_string($id) || !preg_match('/^[a-z0-9-]{1,120}$/', $id)) throw new InvalidArgumentException('Invalid product selection.');
    $ids = array_values(array_unique($ids));
    sort($ids, SORT_STRING);
    return $ids;
}

function gawdee_wishlist_key(array $ids): string
{
    return implode(',', gawdee_wishlist_ids($ids));
}

function gawdee_wishlist_items(): array
{
    $customer = gawdee_customer();
    if (!$customer) return is_array($_SESSION['saved_products'] ?? null) ? $_SESSION['saved_products'] : [];
    $query = gawdee_db()->prepare('SELECT item_key, product_ids FROM saved_products WHERE user_id=? ORDER BY created_at DESC, item_key');
    $query->execute([(int)$customer['id']]);
    $items = [];
    foreach ($query->fetchAll() as $row) $items[$row['item_key']] = json_decode($row['product_ids'], true) ?: [];
    return $items;
}

function gawdee_wishlist_set(array $ids, bool $saved): array
{
    $ids = gawdee_wishlist_ids($ids);
    $key = gawdee_wishlist_key($ids);
    $items = gawdee_wishlist_items();
    if ($saved) {
        if (!isset($items[$key]) && count($items) >= 60) throw new InvalidArgumentException('Your wishlist is full. Remove an item before saving another.');
        foreach ($ids as $id) {
            $product = gawdee_product_by_id($id);
            if (!$product || !(int)$product['is_active']) throw new InvalidArgumentException('This product is no longer available to save.');
        }
    }
    $customer = gawdee_customer();
    if ($customer) {
        if ($saved) {
            $db = gawdee_db();
            $db->prepare(gawdee_sql($db, 'INSERT OR IGNORE INTO saved_products(user_id,item_key,product_ids) VALUES(?,?,?)'))->execute([(int)$customer['id'],$key,json_encode($ids,JSON_THROW_ON_ERROR)]);
        } else {
            gawdee_db()->prepare('DELETE FROM saved_products WHERE user_id=? AND item_key=?')->execute([(int)$customer['id'],$key]);
        }
    } else {
        if ($saved) $items[$key] = $ids; else unset($items[$key]);
        $_SESSION['saved_products'] = $items;
    }
    return gawdee_wishlist_items();
}

function gawdee_wishlist_merge_guest(): void
{
    if (!gawdee_customer()) return;
    foreach (array_slice((array)($_SESSION['saved_products'] ?? []),0,60) as $ids) {
        try { gawdee_wishlist_set((array)$ids, true); } catch (InvalidArgumentException $e) { /* Unavailable products are not imported. */ }
    }
    unset($_SESSION['saved_products']);
}
