<?php declare(strict_types=1); $siteDesign = gawdee_site(); $siteCollections = gawdee_collections(); ?>
</main>

<footer class="commerce-footer gawdee-site-footer" id="site-footer">
    <div class="container commerce-footer__grid">
        <div class="commerce-footer__brand">
            <a class="commerce-footer__logo" href="index.php" aria-label="<?= htmlspecialchars($siteDesign['brand_name']) ?> home">
                <img src="<?= htmlspecialchars($siteDesign['brand_logo']) ?>" alt="<?= htmlspecialchars($siteDesign['brand_name']) ?>">
            </a>
            <p><?= nl2br(htmlspecialchars($siteDesign['footer_description'])) ?></p>
            <div class="commerce-socials" aria-label="Follow us">
                <?php foreach (['facebook'=>'ph-facebook-logo','instagram'=>'ph-instagram-logo','youtube'=>'ph-youtube-logo'] as $network=>$icon): if(!$siteDesign['social_'.$network]) continue; ?><a href="<?= htmlspecialchars($siteDesign['social_'.$network]) ?>" target="_blank" rel="noopener" aria-label="<?= ucfirst($network) ?>"><i class="ph <?= $icon ?>"></i></a><?php endforeach; ?>
            </div>
        </div>
        <?php foreach (['footer_shop'=>'Shop','footer_about'=>'About','footer_help'=>'Help'] as $group=>$label): ?>
        <nav class="commerce-footer__links" aria-label="Footer <?= $label ?> links"><h2><?= $label ?></h2>
        <?php foreach ($siteCollections[$group] as $link): ?><a href="<?= htmlspecialchars(gawdee_public_url($link['url'],'products.php')) ?>"><?= htmlspecialchars($link['title']) ?></a><?php endforeach; ?>
        </nav>
        <?php endforeach; ?>
        <section class="commerce-footer__signup" aria-labelledby="footer-community-title">
            <h2 id="footer-community-title"><?= htmlspecialchars($siteDesign['footer_signup_title']) ?></h2>
            <p><?= htmlspecialchars($siteDesign['footer_signup_text']) ?></p>
            <form class="footer-mini-form" data-newsletter-form>
                <label class="sr-only" for="footer-email">Email address</label>
                <input id="footer-email" type="email" placeholder="Enter your email" autocomplete="email" required>
                <button type="submit" aria-label="Join the healthy living community"><i class="ph ph-arrow-right"></i></button>
            </form>
            <?php if ($siteDesign['app_apple'] || $siteDesign['app_google']): ?>
            <div class="commerce-footer__stores" aria-label="Download our app">
            <?php foreach (['app_apple'=>['ph-apple-logo','Download on the','App Store'],'app_google'=>['ph-google-play-logo','GET IT ON','Google Play']] as $key=>[$icon,$prefix,$label]): if(!$siteDesign[$key]) continue; ?><a href="<?= htmlspecialchars($siteDesign[$key]) ?>" target="_blank" rel="noopener"><i class="ph <?= $icon ?>"></i><span><small><?= $prefix ?></small><strong><?= $label ?></strong></span></a><?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>
    <div class="container commerce-footer__bottom">
        <p>© <?= date('Y') ?> <?= htmlspecialchars($siteDesign['brand_name']) ?>. All rights reserved.</p>
        <nav aria-label="Gawdee values"><a href="index.php#about">Pure by Nature</a><i aria-hidden="true"></i><a href="index.php#farms">Nourishing Generations</a><i aria-hidden="true"></i><a href="index.php#quality-promise">A Healthier Tomorrow</a></nav>
    </div>
</footer>

<?php if ($siteDesign['site_show_chat'] === '1' && gawdee_setting('ai_chat_enabled', '1') === '1'): ?>
<button class="ai-float" type="button" data-ai-toggle aria-label="Open Gawdee AI wellness assistant" aria-expanded="false">
    <span class="ai-float__orb"><img src="assets/images/gawdee-ai-robot-v1.png" alt="" aria-hidden="true"></span>
    <span class="ai-float__online" aria-hidden="true"></span>
</button>
<aside class="ai-chat" data-ai-chat aria-hidden="true" aria-labelledby="ai-chat-title">
    <header>
        <span class="ai-chat__mark"><img src="assets/images/gawdee-ai-robot-v1.png" alt="" aria-hidden="true"></span>
        <div><strong id="ai-chat-title">Ask Gawdee</strong><small><span></span> Product &amp; order help</small></div>
        <button type="button" data-ai-close aria-label="Close assistant"><i class="ph ph-x"></i></button>
    </header>
    <div class="ai-chat__messages" data-ai-messages><div class="ai-message ai-message--assistant">Namaste! What can I help you find today?</div></div>
    <div class="ai-chat__suggestions"><button type="button" data-ai-suggestion="Which Gawdee products are best for an everyday family pantry?"><i class="ph ph-house-line"></i> Family pantry</button><button type="button" data-ai-suggestion="Tell me about Gawdee A2 Gir Cow Ghee."><i class="ph ph-bowl-steam"></i> A2 Ghee</button><button type="button" data-ai-suggestion="How does delivery work?"><i class="ph ph-truck"></i> Delivery</button></div>
    <form data-ai-form><label class="sr-only" for="ai-question">Ask Gawdee AI</label><input id="ai-question" maxlength="700" placeholder="Ask Gawdee anything…" autocomplete="off" required><button type="submit" aria-label="Send message"><i class="ph ph-arrow-up"></i></button></form>
    <p><i class="ph ph-info"></i> AI guidance may vary. Always review product labels.</p>
</aside>
<?php endif; ?>
<?php if ($siteDesign['site_show_whatsapp'] === '1' && gawdee_setting('whatsapp_number')): ?>
<a class="whatsapp-float" href="https://wa.me/<?= htmlspecialchars(preg_replace('/\D+/', '', gawdee_setting('whatsapp_number', '917055207030'))) ?>" target="_blank" rel="noopener" aria-label="Chat with Gawdee on WhatsApp"><i class="ph ph-whatsapp-logo"></i></a>
<?php endif; ?>
<div class="toast" role="status" aria-live="polite" data-toast></div>
<script src="assets/js/app.js?v=<?= rawurlencode((string) ((int) @filemtime(__DIR__ . '/../assets/js/app.js'))) ?>" defer></script>
<script src="assets/js/wishlist.js?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/wishlist.js') ?>" defer></script>
</body>
</html>
