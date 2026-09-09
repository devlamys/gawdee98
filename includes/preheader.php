<?php
declare(strict_types=1);
/**
 * Shared site preheader (single source of truth).
 * Design source: index/homepage `.sf-reference-promo` TV-tag/promotion strip.
 * Included by includes/header.php so EVERY page renders the SAME preheader.
 * Backend stays dynamic — no hardcoded text, links, icons or thresholds.
 *
 * Expects (set by header.php before include):
 *   $siteDesign      from gawdee_site()
 *   $siteCollections from gawdee_collections()
 */
if (!isset($siteDesign) || !isset($siteCollections)) {
    require_once __DIR__ . '/storefront.php';
    $siteDesign = $siteDesign ?? gawdee_site();
    $siteCollections = $siteCollections ?? gawdee_collections();
}
$preheaderBenefits = $siteCollections['header_benefits'] ?? [];
$preheaderThreshold = number_format((int) gawdee_setting('free_shipping_threshold', '999'));
?>
<div class="sf-reference-promo" aria-label="The Gawdee promise">
    <ul>
        <?php foreach ($preheaderBenefits as $benefit): ?>
            <li>
                <i class="ph <?= htmlspecialchars($benefit['icon'] ?? 'ph-leaf') ?>" aria-hidden="true"></i>
                <span><?= htmlspecialchars(str_replace('{threshold}', $preheaderThreshold, (string) ($benefit['title'] ?? ''))) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <span class="sf-reference-promo__tagline"><?= htmlspecialchars((string) ($siteDesign['brand_tagline'] ?? '')) ?></span>
</div>
