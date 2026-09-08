<?php

$queueMetrics = gawdee_db()->query(<<<'SQL'
SELECT
    SUM(CASE WHEN status IN ('queued','retry') THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status IN ('sent','delivered','read') THEN 1 ELSE 0 END) AS sent,
    SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) AS failed
FROM notification_queue
SQL)->fetch() ?: [];
$optedIn = (int) gawdee_db()->query("SELECT COUNT(*) FROM users WHERE role='customer' AND whatsapp_marketing_opt_in=1 AND whatsapp_opt_out_at IS NULL")->fetchColumn();
?>

<div class="admin-section-title"><div><span class="section-kicker">Secure operations</span><h2>Payments, delivery & messaging</h2><p>Production credentials are encrypted and never shown again after saving.</p></div></div>

<div class="integration-grid" style="margin-bottom:20px">
    <?php foreach ([
        ['Razorpay', gawdee_razorpay_configured(), 'ph-credit-card', 'Server-verified online payments'],
        ['Delhivery', gawdee_delhivery_configured(), 'ph-truck', 'Pincode checks, waybills and tracking'],
        ['WhatsApp Cloud', gawdee_whatsapp_configured(), 'ph-whatsapp-logo', 'OTP and approved message templates'],
    ] as [$name, $ready, $icon, $description]): ?>
        <section class="integration-mode-card <?= $ready ? 'is-online' : 'is-offline' ?>" style="margin:0"><div><span class="integration-mode-card__icon"><i class="ph <?= $icon ?>"></i></span><div><strong><?= htmlspecialchars($name) ?> · <?= $ready ? 'ready' : 'setup needed' ?></strong><p><?= htmlspecialchars($description) ?></p></div></div></section>
    <?php endforeach; ?>
</div>

<div class="integration-grid" style="margin-bottom:20px">
    <section class="integration-card">
        <div class="integration-card__title"><i class="ph ph-credit-card"></i><div><h3>Razorpay</h3><p>Orders API, captured-payment validation and signed webhooks.</p></div></div>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="save_integrations">
            <label><span>Key ID</span><input name="razorpay_key_id" value="<?= htmlspecialchars(gawdee_setting('razorpay_key_id')) ?>" autocomplete="off"></label>
            <label><span>Key secret</span><input type="password" name="razorpay_key_secret" placeholder="Leave blank to keep stored secret" autocomplete="new-password"><small class="secret-state"><i class="ph ph-lock"></i> <?= gawdee_setting('razorpay_key_secret') !== '' ? 'Configured' : 'Not configured' ?></small></label>
            <label><span>Webhook secret</span><input type="password" name="razorpay_webhook_secret" placeholder="Leave blank to keep stored secret" autocomplete="new-password"><small class="secret-state"><i class="ph ph-lock"></i> <?= gawdee_setting('razorpay_webhook_secret') !== '' ? 'Configured' : 'Not configured' ?></small></label>
            <div><span class="help-text">Webhook endpoint</span><div class="code-box"><?= htmlspecialchars(gawdee_base_url() . '/api/razorpay-webhook.php') ?></div><p class="help-text">Subscribe to payment.captured, payment.failed and order.paid.</p></div>
            <button class="admin-button admin-button--primary">Save Razorpay</button>
        </form>
    </section>

    <section class="integration-card">
        <div class="integration-card__title"><i class="ph ph-truck"></i><div><h3>Delhivery One</h3><p>Official B2C serviceability, manifestation, labels and tracking.</p></div></div>
        <form method="post" class="admin-form form-grid">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="save_integrations">
            <label><span>Environment</span><select name="delhivery_environment"><option value="staging" <?= gawdee_setting('delhivery_environment','staging') === 'staging' ? 'selected' : '' ?>>Staging</option><option value="production" <?= gawdee_setting('delhivery_environment') === 'production' ? 'selected' : '' ?>>Production</option></select></label>
            <label><span>API token</span><input type="password" name="delhivery_api_token" placeholder="Keep stored token" autocomplete="new-password"><small class="secret-state"><?= gawdee_setting('delhivery_api_token') !== '' ? 'Token configured' : 'Not configured' ?></small></label>
            <label><span>Pickup location name</span><input name="delhivery_pickup_location" value="<?= htmlspecialchars(gawdee_setting('delhivery_pickup_location')) ?>" placeholder="Must exactly match Delhivery One"></label>
            <label><span>Client name</span><input name="delhivery_client_name" value="<?= htmlspecialchars(gawdee_setting('delhivery_client_name')) ?>"></label>
            <label><span>Warehouse name</span><input name="delhivery_origin_name" value="<?= htmlspecialchars(gawdee_setting('delhivery_origin_name','Gawdee Warehouse')) ?>"></label>
            <label><span>Warehouse phone</span><input name="delhivery_origin_phone" value="<?= htmlspecialchars(gawdee_setting('delhivery_origin_phone')) ?>"></label>
            <label class="form-span-2"><span>Warehouse address</span><input name="delhivery_origin_address" value="<?= htmlspecialchars(gawdee_setting('delhivery_origin_address')) ?>"></label>
            <label><span>City</span><input name="delhivery_origin_city" value="<?= htmlspecialchars(gawdee_setting('delhivery_origin_city')) ?>"></label>
            <label><span>State</span><input name="delhivery_origin_state" value="<?= htmlspecialchars(gawdee_setting('delhivery_origin_state')) ?>"></label>
            <label><span>Pincode</span><input name="delhivery_origin_pincode" maxlength="6" value="<?= htmlspecialchars(gawdee_setting('delhivery_origin_pincode')) ?>"></label>
            <label><span>Default grams / item</span><input type="number" min="100" name="delhivery_default_weight_grams" value="<?= (int) gawdee_setting('delhivery_default_weight_grams','500') ?>"></label>
            <label><span>Length cm</span><input type="number" min="1" name="delhivery_default_length_cm" value="<?= (int) gawdee_setting('delhivery_default_length_cm','20') ?>"></label>
            <label><span>Width cm</span><input type="number" min="1" name="delhivery_default_width_cm" value="<?= (int) gawdee_setting('delhivery_default_width_cm','15') ?>"></label>
            <label><span>Height cm</span><input type="number" min="1" name="delhivery_default_height_cm" value="<?= (int) gawdee_setting('delhivery_default_height_cm','10') ?>"></label>
            <label><span>Webhook shared token</span><input type="password" name="delhivery_webhook_secret" placeholder="Set a long random value" autocomplete="new-password"><small class="secret-state"><?= gawdee_setting('delhivery_webhook_secret') !== '' ? 'Configured' : 'Not configured' ?></small></label>
            <div class="form-span-2"><span class="help-text">Tracking webhook</span><div class="code-box"><?= htmlspecialchars(gawdee_base_url() . '/api/delhivery-webhook.php?token=YOUR_SHARED_TOKEN') ?></div></div>
            <div class="form-span-2" style="display:flex;justify-content:flex-end"><button class="admin-button admin-button--primary">Save Delhivery</button></div>
        </form>
        <form method="post" style="margin-top:12px"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="toggle_delhivery"><button class="admin-button <?= gawdee_setting('delhivery_enabled','0') === '1' ? 'admin-button--danger' : 'admin-button--secondary' ?>"><?= gawdee_setting('delhivery_enabled','0') === '1' ? 'Disable Delhivery' : 'Enable Delhivery' ?></button></form>
    </section>
</div>

<section class="integration-card" style="margin-bottom:20px">
    <div class="integration-card__title"><i class="ph ph-whatsapp-logo"></i><div><h3>WhatsApp Cloud API</h3><p>Authentication OTP, transactional order templates, delivery receipts and consent-based campaigns.</p></div></div>
    <form method="post" class="admin-form form-grid">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="save_integrations"><input type="hidden" name="whatsapp_settings_form" value="1">
        <label><span>Graph API version</span><input name="whatsapp_graph_version" value="<?= htmlspecialchars(gawdee_setting('whatsapp_graph_version','v23.0')) ?>" pattern="v[0-9]+\.[0-9]+"></label>
        <label><span>Language</span><input name="whatsapp_language" value="<?= htmlspecialchars(gawdee_setting('whatsapp_language','en_US')) ?>"></label>
        <label><span>Phone number ID</span><input name="whatsapp_phone_number_id" value="<?= htmlspecialchars(gawdee_setting('whatsapp_phone_number_id')) ?>"></label>
        <label><span>WhatsApp Business Account ID</span><input name="whatsapp_business_account_id" value="<?= htmlspecialchars(gawdee_setting('whatsapp_business_account_id')) ?>"></label>
        <label><span>Permanent access token</span><input type="password" name="whatsapp_access_token" placeholder="Leave blank to keep stored token" autocomplete="new-password"><small class="secret-state"><?= gawdee_setting('whatsapp_access_token') !== '' ? 'Configured' : 'Not configured' ?></small></label>
        <label><span>Meta app secret</span><input type="password" name="whatsapp_app_secret" placeholder="Used to validate webhooks" autocomplete="new-password"><small class="secret-state"><?= gawdee_setting('whatsapp_app_secret') !== '' ? 'Configured' : 'Not configured' ?></small></label>
        <label><span>Webhook verify token</span><input type="password" name="whatsapp_verify_token" placeholder="Set a long random value" autocomplete="new-password"><small class="secret-state"><?= gawdee_setting('whatsapp_verify_token') !== '' ? 'Configured' : 'Not configured' ?></small></label>
        <label><span>Queue scheduler token</span><input type="password" name="notification_cron_token" placeholder="Set a long random value" autocomplete="new-password"><small class="secret-state"><?= gawdee_setting('notification_cron_token') !== '' ? 'Configured' : 'Not configured' ?></small></label>
        <label><span>OTP template</span><input name="whatsapp_template_otp" value="<?= htmlspecialchars(gawdee_setting('whatsapp_template_otp')) ?>"></label>
        <label><span>Order confirmed template</span><input name="whatsapp_template_order_confirmed" value="<?= htmlspecialchars(gawdee_setting('whatsapp_template_order_confirmed')) ?>"></label>
        <label><span>Payment confirmed template</span><input name="whatsapp_template_payment_confirmed" value="<?= htmlspecialchars(gawdee_setting('whatsapp_template_payment_confirmed')) ?>"></label>
        <label><span>Packed template</span><input name="whatsapp_template_order_packed" value="<?= htmlspecialchars(gawdee_setting('whatsapp_template_order_packed')) ?>"></label>
        <label><span>Shipped template</span><input name="whatsapp_template_order_shipped" value="<?= htmlspecialchars(gawdee_setting('whatsapp_template_order_shipped')) ?>"></label>
        <label><span>Delivered template</span><input name="whatsapp_template_order_delivered" value="<?= htmlspecialchars(gawdee_setting('whatsapp_template_order_delivered')) ?>"></label>
        <label><span>Cancelled template</span><input name="whatsapp_template_order_cancelled" value="<?= htmlspecialchars(gawdee_setting('whatsapp_template_order_cancelled')) ?>"></label>
        <label><span>Marketing template</span><input name="whatsapp_template_marketing" value="<?= htmlspecialchars(gawdee_setting('whatsapp_template_marketing')) ?>"></label>
        <label class="form-switch"><input type="checkbox" name="whatsapp_otp_enabled" value="1" <?= gawdee_setting('whatsapp_otp_enabled') === '1' ? 'checked' : '' ?>><span>Enable WhatsApp OTP login</span></label>
        <label class="form-switch"><input type="checkbox" name="whatsapp_order_notifications" value="1" <?= gawdee_setting('whatsapp_order_notifications','1') === '1' ? 'checked' : '' ?>><span>Send order notifications</span></label>
        <label class="form-switch"><input type="checkbox" name="whatsapp_marketing_enabled" value="1" <?= gawdee_setting('whatsapp_marketing_enabled') === '1' ? 'checked' : '' ?>><span>Enable consent-based marketing</span></label>
        <div class="form-span-2"><span class="help-text">Meta callback URL</span><div class="code-box"><?= htmlspecialchars(gawdee_base_url() . '/api/whatsapp-webhook.php') ?></div><p class="help-text">Templates must be approved in WhatsApp Manager and their placeholder count must match. Transactional variables are customer name, order number and total; shipped adds courier and tracking.</p></div>
        <div class="form-span-2" style="display:flex;justify-content:flex-end"><button class="admin-button admin-button--primary">Save WhatsApp</button></div>
    </form>
    <form method="post" style="margin-top:12px"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="toggle_whatsapp"><button class="admin-button <?= gawdee_setting('whatsapp_cloud_enabled','0') === '1' ? 'admin-button--danger' : 'admin-button--secondary' ?>"><?= gawdee_setting('whatsapp_cloud_enabled','0') === '1' ? 'Disable WhatsApp sending' : 'Enable WhatsApp sending' ?></button></form>
</section>

<div class="integration-grid">
    <section class="integration-card"><div class="integration-card__title"><i class="ph ph-queue"></i><div><h3>Notification queue</h3><p><?= (int) ($queueMetrics['pending'] ?? 0) ?> pending · <?= (int) ($queueMetrics['sent'] ?? 0) ?> sent/delivered/read · <?= (int) ($queueMetrics['failed'] ?? 0) ?> failed</p></div></div><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="process_notifications"><button class="admin-button admin-button--primary"><i class="ph ph-paper-plane-tilt"></i> Process queue now</button></form><div style="margin-top:16px"><span class="help-text">Scheduler URL (run every minute)</span><div class="code-box"><?= htmlspecialchars(gawdee_base_url() . '/cron/process-notifications.php?token=YOUR_CRON_TOKEN') ?></div></div></section>
    <section class="integration-card"><div class="integration-card__title"><i class="ph ph-megaphone"></i><div><h3>Marketing campaign</h3><p><?= $optedIn ?> customer<?= $optedIn === 1 ? '' : 's' ?> currently opted in. Only approved templates are sent.</p></div></div><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="queue_whatsapp_marketing"><label><span>Approved template name</span><input name="template_name" value="<?= htmlspecialchars(gawdee_setting('whatsapp_template_marketing')) ?>" required></label><label><span>Template variables</span><textarea name="parameters" placeholder="One placeholder value per line"></textarea></label><button class="admin-button admin-button--primary"><i class="ph ph-users-three"></i> Queue for opted-in customers</button><p class="help-text">Customers can reply STOP to withdraw consent. The webhook records that opt-out before future campaigns.</p></form></section>
</div>
