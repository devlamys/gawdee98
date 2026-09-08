<?php $collections = gawdee_collections(); $catalogue = gawdee_products(true); ?>
<div class="admin-section-title"><div><h2>Storefront collections</h2><p>Manage navigation, category cards, bestsellers, complete combo packs and quality cards.</p></div><a class="admin-button admin-button--secondary" href="?view=cms">Section titles & visibility <i class="ph ph-arrow-right"></i></a></div>
<section class="admin-card"><div class="admin-card__body"><details><summary>Product IDs for combo packs</summary><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Product</th><th>ID</th><th>Status</th></tr></thead><tbody><?php foreach ($catalogue as $p): ?><tr><td><?= htmlspecialchars($p['full_name']) ?></td><td><code><?= htmlspecialchars($p['id']) ?></code></td><td><?= $p['is_active'] ? 'Active' : 'Hidden' ?></td></tr><?php endforeach; ?></tbody></table></div></details></div></section>
<form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
    <input type="hidden" name="action" value="save_collections">
    <?php foreach (gawdee_collection_defaults() as $group => $defaultRows): ?>
    <section class="admin-card"><div class="admin-card__header"><div><h2><?= htmlspecialchars(ucfirst($group)) ?></h2><p><?= $group === 'combos' ? 'Each image should show every product in the combo. Prices and stock come from the selected products.' : 'Edit a card below or add another. Use Up and Down to change its order.' ?></p></div><button type="button" class="admin-button admin-button--secondary" data-collection-add="<?= $group ?>">Add row <i class="ph ph-plus"></i></button></div>
        <div class="admin-card__body" data-collection-rows="<?= $group ?>">
            <?php foreach (array_merge($collections[$group], [array_fill_keys(array_keys($defaultRows[0]), '')]) as $index => $row): ?>
            <fieldset class="collection-editor" <?= $index === count($collections[$group]) ? 'data-collection-template hidden disabled' : '' ?>>
                <legend><?= htmlspecialchars(ucfirst($group)) ?> item</legend>
                <div class="form-grid">
                <?php foreach ($row as $field => $value): ?><label class="<?= in_array($field,['text','product_ids'],true) ? 'form-span-2' : '' ?>"><span><?= htmlspecialchars(ucwords(str_replace('_',' ', $field))) ?></span>
                    <?php if ($field === 'product_id'): ?><select name="<?= $group ?>[<?= $index ?>][<?= $field ?>]"><option value="">Choose product</option><?php foreach ($catalogue as $p): ?><option value="<?= htmlspecialchars($p['id']) ?>" <?= $value === $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['full_name']) ?></option><?php endforeach; ?></select>
                    <?php elseif ($field === 'text' || str_contains($value,"\n")): ?><textarea name="<?= $group ?>[<?= $index ?>][<?= $field ?>]"><?= htmlspecialchars($value) ?></textarea>
                    <?php else: ?><input name="<?= $group ?>[<?= $index ?>][<?= $field ?>]" value="<?= htmlspecialchars($value) ?>">
                    <?php endif; ?>
                    <?php if (in_array($field,['image','hover_image'],true)): ?><input type="file" name="<?= $group ?>_<?= $index ?>_<?= $field ?>" accept="image/png,image/jpeg,image/webp" data-collection-upload="<?= $field ?>"><?php endif; ?>
                    <?php if($field === 'image_crop'): ?><small>Reference artwork region: x,y,width,height,source width,source height. Leave blank for your own complete card. Uploading an image clears the crop automatically.</small><?php endif; ?>
                    <?php if($field === 'features'): ?><small>Three short benefits separated by |. These appear below the product image.</small><?php endif; ?>
                </label><?php endforeach; ?>
                </div>
                <div class="admin-actions"><button type="button" class="admin-button admin-button--ghost" data-collection-up aria-label="Move item up">↑ Up</button><button type="button" class="admin-button admin-button--ghost" data-collection-down aria-label="Move item down">↓ Down</button><label class="form-switch"><input type="checkbox" name="<?= $group ?>[<?= $index ?>][remove]" value="1"><span>Remove this item</span></label></div>
            </fieldset>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
    <div class="sticky-save-bar"><span>Hidden products are excluded automatically.</span><button class="admin-button admin-button--primary">Save collections <i class="ph ph-check"></i></button></div>
</form>
