<?php
$profile = $p;
foreach ($products as $candidate) if ($candidate['id'] === $p['id']) $profile = $candidate;
$gallery = $profile['gallery'] ?? [];
$hover = count($gallery) > 1 ? (string) ($gallery[1]['src'] ?? '') : '';
$joinLines = static fn(array $items): string => implode("\n", $items);
$faqsText = $joinLines(array_map(static fn($item) => $item['question'].' | '.$item['answer'], $profile['faqs'] ?? []));
$benefitsText = $joinLines(array_map(static fn($item) => implode(' | ', $item), $profile['benefits'] ?? []));
?>
<section class="admin-card" style="margin-bottom:20px">
<div class="admin-card__header"><div><h2><?= $editProduct ? 'Edit product' : 'Create product' ?></h2><p>Images, ingredients, benefits, usage and FAQs are used on the storefront.</p></div><a href="?view=products" class="admin-action-icon" aria-label="Close editor"><i class="ph ph-x"></i></a></div>
<div class="admin-card__body"><form method="post" enctype="multipart/form-data" class="admin-form form-grid form-grid--3">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="save_product">
    <?php foreach (['id'=>'Product ID','slug'=>'Slug','name'=>'Short name','full_name'=>'Full name','category'=>'Category','category_key'=>'Category key','tag'=>'Badge','weight'=>'Pack size'] as $key => $label): ?>
    <label><span><?= $label ?></span><input name="<?= $key ?>" value="<?= htmlspecialchars((string)($p[$key] ?? '')) ?>" <?= $key === 'id' && $editProduct ? 'readonly' : '' ?> <?= in_array($key,['name','full_name','slug'],true) ? 'required' : '' ?>></label>
    <?php endforeach; ?>
    <?php foreach (['price'=>'Selling price ₹','original_price'=>'MRP ₹','stock'=>'Stock units'] as $key=>$label): ?><label><span><?= $label ?></span><input type="number" min="0" name="<?= $key ?>" value="<?= (int)($p[$key]??0) ?>"></label><?php endforeach; ?>
    <label><span>Accent colour</span><input type="color" name="accent" value="<?= htmlspecialchars($p['accent']) ?>"></label>
    <label class="form-span-3"><span>Description</span><textarea name="description"><?= htmlspecialchars($p['description']) ?></textarea></label>
    <label><span>Product subtitle (optional)</span><input name="subtitle" value="<?= htmlspecialchars($profile['editor']['subtitle'] ?? '') ?>"></label>
    <label><span>Sourcing story heading (optional)</span><input name="story_title" value="<?= htmlspecialchars($profile['editor']['story_title'] ?? '') ?>"></label>
    <label><span>Sourcing story text (optional)</span><textarea name="story_text"><?= htmlspecialchars($profile['editor']['story_text'] ?? '') ?></textarea></label>
    <label><span>Main image path</span><input name="image" value="<?= htmlspecialchars($p['image']) ?>"><input type="file" name="product_image_upload" accept="image/png,image/jpeg,image/webp"><small class="help-text">Choose a new file or keep the current path.</small></label>
    <label><span>Second / hover image</span><input name="hover_image" value="<?= htmlspecialchars($hover) ?>"><input type="file" name="product_hover_upload" accept="image/png,image/jpeg,image/webp"><small class="help-text">Used when customers hover over a product.</small></label>
    <label><span>Additional gallery images</span><textarea name="gallery_paths" placeholder="One image path per line"><?= htmlspecialchars($joinLines(array_column(array_slice($gallery,2),'src'))) ?></textarea></label>
    <?php foreach (['ingredients'=>'Ingredients — one per line','overview_points'=>'Product information — one point per line','usage'=>'Directions — one step per line'] as $field=>$label): ?><label><span><?= $label ?></span><textarea name="<?= $field ?>"><?= htmlspecialchars($joinLines($profile[$field]??[])) ?></textarea></label><?php endforeach; ?>
    <label class="form-span-3"><span>Benefits — icon | title | description, one per line</span><textarea name="benefits_text"><?= htmlspecialchars($benefitsText) ?></textarea></label>
    <label class="form-span-3"><span>FAQs — question | answer, one per line</span><textarea name="faqs_text"><?= htmlspecialchars($faqsText) ?></textarea></label>
    <label class="form-span-3"><span>Usage cards — icon | title | description, one per line (optional)</span><textarea name="use_cards_text"><?= htmlspecialchars($joinLines(array_map(static fn($row)=>implode(' | ',$row),$profile['editor']['use_cards']??[]))) ?></textarea><small class="help-text">Up to six. Leave blank to use the product's directions automatically.</small></label>
    <label class="form-span-3"><span>Comparison — this product | comparison product, one per line (optional)</span><textarea name="comparison_text"><?= htmlspecialchars($joinLines(array_map(static fn($row)=>implode(' | ',array_values($row)),$profile['comparison_rows']??[]))) ?></textarea><small class="help-text">Only include claims you can substantiate. Leave blank to hide the comparison.</small></label>
    <label class="form-span-2"><span>Story images — one image path per line</span><textarea name="story_paths"><?= htmlspecialchars($joinLines(array_column($profile['aplus_images']??[],'src'))) ?></textarea></label>
    <label><span>Storage instructions</span><textarea name="storage"><?= htmlspecialchars($profile['storage']??'') ?></textarea></label>
    <label class="form-switch"><input type="checkbox" name="is_active" <?= $p['is_active'] ? 'checked' : '' ?>><span>Visible on storefront</span></label>
    <div class="form-span-2 form-submit-row"><button class="admin-button admin-button--primary">Save product <i class="ph ph-check"></i></button></div>
</form></div></section>
