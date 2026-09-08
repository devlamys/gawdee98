<?php

$sectionItemLabels = [
    'benefits' => 'Benefits bar',
    'process' => 'Farm process',
    'assurance' => 'Assurance bar',
    'why' => 'Why choose Gawdee',
    'newsletter-perks' => 'Newsletter perks',
];
$selectedItemSection = array_key_exists((string) ($_GET['section'] ?? ''), $sectionItemLabels) ? (string) $_GET['section'] : 'benefits';
$allSectionItems = gawdee_section_items(null, true);
$visibleSectionItems = array_values(array_filter($allSectionItems, static fn (array $item): bool => $item['section_key'] === $selectedItemSection));
$sectionItemEditId = (int) ($_GET['edit'] ?? 0);
$editSectionItem = null;
foreach ($allSectionItems as $candidate) {
    if ((int) $candidate['id'] === $sectionItemEditId) {
        $editSectionItem = $candidate;
        $selectedItemSection = $candidate['section_key'];
        break;
    }
}
if ($editSectionItem) {
    $visibleSectionItems = array_values(array_filter($allSectionItems, static fn (array $item): bool => $item['section_key'] === $selectedItemSection));
}
$sectionItem = $editSectionItem ?? [
    'id' => 0, 'section_key' => $selectedItemSection, 'icon' => 'ph-leaf', 'title' => '',
    'subtitle' => '', 'image' => '', 'link_url' => '', 'sort_order' => count($visibleSectionItems) * 10 + 10, 'is_active' => 1,
];
?>
<div class="admin-section-title">
    <div><h2>Homepage section item manager</h2><p>Edit the individual benefits, process steps, assurance points and newsletter perks without touching code.</p></div>
    <a class="admin-button admin-button--primary" href="?view=section-items&section=<?= rawurlencode($selectedItemSection) ?>&edit=-1"><i class="ph ph-plus"></i> Add item</a>
</div>

<nav class="cms-item-tabs" aria-label="Homepage item groups">
    <?php foreach ($sectionItemLabels as $key => $label): ?><a class="<?= $selectedItemSection === $key ? 'is-active' : '' ?>" href="?view=section-items&section=<?= rawurlencode($key) ?>"><?= htmlspecialchars($label) ?><span><?= count(array_filter($allSectionItems, static fn (array $item): bool => $item['section_key'] === $key)) ?></span></a><?php endforeach; ?>
</nav>

<?php if (isset($_GET['edit'])): ?>
<section class="admin-card cms-form-card">
    <div class="admin-card__header"><div><h2><?= $editSectionItem ? 'Edit section item' : 'New section item' ?></h2><p>Use a Phosphor icon class such as ph-leaf, ph-cow, ph-fire or ph-seal-check.</p></div><a href="?view=section-items&section=<?= rawurlencode($selectedItemSection) ?>" class="admin-action-icon"><i class="ph ph-x"></i></a></div>
    <div class="admin-card__body">
        <form method="post" enctype="multipart/form-data" class="admin-form form-grid form-grid--3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_section_item">
            <input type="hidden" name="id" value="<?= (int) $sectionItem['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($sectionItem['image']) ?>">
            <label><span>Homepage block</span><select name="section_key"><?php foreach ($sectionItemLabels as $key => $label): ?><option value="<?= htmlspecialchars($key) ?>" <?= $sectionItem['section_key'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Icon class</span><input name="icon" value="<?= htmlspecialchars($sectionItem['icon']) ?>" placeholder="ph-leaf"></label>
            <label><span>Sort order</span><input type="number" name="sort_order" value="<?= (int) $sectionItem['sort_order'] ?>"></label>
            <label><span>Title</span><input name="title" required value="<?= htmlspecialchars($sectionItem['title']) ?>"></label>
            <label class="form-span-2"><span>Supporting text</span><input name="subtitle" value="<?= htmlspecialchars($sectionItem['subtitle']) ?>"></label>
            <label><span>Optional image</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small class="help-text"><?= htmlspecialchars($sectionItem['image']) ?></small></label>
            <label class="form-span-2"><span>Optional destination link</span><input name="link_url" value="<?= htmlspecialchars($sectionItem['link_url']) ?>" placeholder="products.php or #shop"></label>
            <label class="form-switch"><input type="checkbox" name="is_active" <?= $sectionItem['is_active'] ? 'checked' : '' ?>><span>Visible on homepage</span></label>
            <div class="form-span-3 form-submit-row"><button class="admin-button admin-button--primary">Save section item <i class="ph ph-check"></i></button></div>
        </form>
    </div>
</section>
<?php endif; ?>

<div class="cms-section-item-grid">
    <?php foreach ($visibleSectionItems as $item): ?>
    <article class="cms-section-item-card <?= $item['is_active'] ? '' : 'is-hidden' ?>">
        <span class="cms-section-item-card__icon"><?php if ($item['image']): ?><img src="../<?= htmlspecialchars($item['image']) ?>" alt=""><?php else: ?><i class="ph <?= htmlspecialchars($item['icon']) ?>"></i><?php endif; ?></span>
        <div><small><?= htmlspecialchars($sectionItemLabels[$item['section_key']] ?? $item['section_key']) ?> · Order <?= (int) $item['sort_order'] ?></small><h3><?= htmlspecialchars($item['title']) ?></h3><p><?= htmlspecialchars($item['subtitle']) ?></p></div>
        <div class="admin-actions"><a class="admin-action-icon" href="?view=section-items&section=<?= rawurlencode($selectedItemSection) ?>&edit=<?= (int) $item['id'] ?>"><i class="ph ph-pencil-simple"></i></a><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="toggle_section_item"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="section_key" value="<?= htmlspecialchars($selectedItemSection) ?>"><button class="admin-action-icon"><i class="ph <?= $item['is_active'] ? 'ph-eye-slash' : 'ph-eye' ?>"></i></button></form><form method="post" onsubmit="return confirm('Delete this section item?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="delete_section_item"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="section_key" value="<?= htmlspecialchars($selectedItemSection) ?>"><button class="admin-action-icon admin-action-icon--danger"><i class="ph ph-trash"></i></button></form></div>
    </article>
    <?php endforeach; ?>
</div>
