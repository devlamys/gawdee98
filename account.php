<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$customer = gawdee_require_customer('account.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        gawdee_verify_csrf($_POST['csrf_token'] ?? null);
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'profile') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $address1 = trim((string) ($_POST['address1'] ?? ''));
            $address2 = trim((string) ($_POST['address2'] ?? ''));
            $city = trim((string) ($_POST['city'] ?? ''));
            $state = trim((string) ($_POST['state'] ?? ''));
            $pincode = trim((string) ($_POST['pincode'] ?? ''));
            if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
                throw new RuntimeException('Enter your full name.');
            }
            if ($phone !== '' && !preg_match('/^[0-9+()\s-]{8,18}$/', $phone)) {
                throw new RuntimeException('Enter a valid phone number.');
            }
            if ($pincode !== '' && !preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
                throw new RuntimeException('Enter a valid six-digit pincode.');
            }
            $marketingOptIn = isset($_POST['whatsapp_marketing_opt_in']) ? 1 : 0;
            gawdee_db()->prepare("UPDATE users SET name=?, phone=?, address1=?, address2=?, city=?, state=?, pincode=?, whatsapp_marketing_opt_in=?, whatsapp_marketing_opt_in_at=CASE WHEN ?=1 AND whatsapp_marketing_opt_in=0 THEN CURRENT_TIMESTAMP ELSE whatsapp_marketing_opt_in_at END, whatsapp_opt_out_at=CASE WHEN ?=1 THEN NULL ELSE CURRENT_TIMESTAMP END, updated_at=CURRENT_TIMESTAMP WHERE id=? AND role='customer'")->execute([$name, $phone, $address1, $address2, $city, $state, $pincode, $marketingOptIn, $marketingOptIn, $marketingOptIn, (int) $customer['id']]);
            $_SESSION['account_flash'] = ['type' => 'success', 'message' => 'Your profile and delivery details were updated.'];
        } elseif ($action === 'password') {
            $current = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmation = (string) ($_POST['new_password_confirmation'] ?? '');
            $statement = gawdee_db()->prepare('SELECT password_hash FROM users WHERE id=?');
            $statement->execute([(int) $customer['id']]);
            if (!password_verify($current, (string) $statement->fetchColumn())) {
                throw new RuntimeException('Your current password is incorrect.');
            }
            if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
                throw new RuntimeException('Use at least 8 characters with a letter and a number.');
            }
            if (!hash_equals($newPassword, $confirmation)) {
                throw new RuntimeException('The new password confirmation does not match.');
            }
            gawdee_db()->prepare('UPDATE users SET password_hash=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int) $customer['id']]);
            $_SESSION['account_flash'] = ['type' => 'success', 'message' => 'Your password was changed securely.'];
        }
    } catch (Throwable $exception) {
        $_SESSION['account_flash'] = ['type' => 'error', 'message' => $exception->getMessage()];
    }
    header('Location: account.php#profile');
    exit;
}

$customer = gawdee_customer() ?? $customer;
$orders = gawdee_customer_orders((int) $customer['id']);
$activeOrders = count(array_filter($orders, static fn(array $order): bool => !in_array($order['status'], ['delivered', 'cancelled', 'refunded'], true)));
$deliveredOrders = count(array_filter($orders, static fn(array $order): bool => $order['status'] === 'delivered'));
$totalSpent = array_sum(array_map(static fn(array $order): int => in_array($order['status'], ['cancelled', 'refunded'], true) ? 0 : (int) $order['total'], $orders));
$flash = $_SESSION['account_flash'] ?? null;
unset($_SESSION['account_flash']);

$pageTitle = 'My account | Gawdee';
$pageDescription = 'Your Gawdee order history, shipment tracking and delivery profile.';
$bodyClass = 'customer-account-page';
require __DIR__ . '/includes/header.php';
?>
<section class="account-hero">
    <div class="container account-hero__inner">
        <div><span class="eyebrow eyebrow--light">My Gawdee</span><h1>Namaste, <em><?= htmlspecialchars(explode(' ', trim((string) $customer['name']))[0]) ?>.</em></h1><p>Every order, payment update and delivery journey—kept together in one calm place.</p></div>
        <div class="account-hero__identity"><span><?= htmlspecialchars(strtoupper(substr((string) $customer['name'], 0, 1))) ?></span><div><strong><?= htmlspecialchars($customer['name']) ?></strong><small><?= htmlspecialchars($customer['email']) ?></small></div><a href="logout.php">Sign out <i class="ph ph-sign-out"></i></a></div>
    </div>
</section>

<nav class="account-nav"><div class="container"><a href="#overview"><i class="ph ph-squares-four"></i> Overview</a><a href="#orders"><i class="ph ph-package"></i> My orders</a><a href="#profile"><i class="ph ph-user-circle"></i> Profile</a><a href="index.php#shop"><i class="ph ph-shopping-bag"></i> Continue shopping</a></div></nav>

<section class="account-shell section" id="overview">
    <div class="container">
        <?php if ($flash): ?><div class="account-alert account-alert--<?= $flash['type'] === 'error' ? 'error' : 'success' ?>"><i class="ph <?= $flash['type'] === 'error' ? 'ph-warning-circle' : 'ph-check-circle' ?>"></i><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
        <div class="account-stats">
            <article><i class="ph ph-receipt"></i><span>Total orders</span><strong><?= count($orders) ?></strong><small>Your complete Gawdee history</small></article>
            <article><i class="ph ph-truck"></i><span>On the way</span><strong><?= $activeOrders ?></strong><small>Orders still in progress</small></article>
            <article><i class="ph ph-package"></i><span>Delivered</span><strong><?= $deliveredOrders ?></strong><small>Orders that reached you</small></article>
            <article><i class="ph ph-wallet"></i><span>Order value</span><strong><?= money($totalSpent) ?></strong><small>Excluding cancelled orders</small></article>
        </div>

        <section class="account-orders" id="orders">
            <div class="account-section-heading"><div><span class="eyebrow">Order history</span><h2>Your purchases & <em>tracking.</em></h2></div><a class="button button--primary" href="index.php#shop">Shop products <i class="ph ph-arrow-right"></i></a></div>
            <?php if ($orders): ?><div class="account-order-list">
                <?php foreach ($orders as $order):
                    $statusPositions = ['pending'=>12,'processing'=>30,'packed'=>52,'shipped'=>76,'delivered'=>100,'cancelled'=>100,'refunded'=>100];
                    $progress = $statusPositions[$order['status']] ?? 12;
                    $accountTrackingReference = gawdee_order_tracking_reference($order);
                    $accountTrackingUrl = gawdee_order_tracking_url($order);
                ?>
                    <article class="account-order-card">
                        <div class="account-order-card__head"><div><span>Order <?= htmlspecialchars($order['order_number']) ?></span><small>Placed <?= htmlspecialchars(date('j M Y, g:i a', strtotime((string) $order['created_at']))) ?></small></div><strong class="account-status account-status--<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars(ucfirst($order['status'])) ?></strong></div>
                        <div class="account-order-card__body"><div><small>ORDER TOTAL</small><strong><?= money((int) $order['total']) ?></strong></div><div><small>PAYMENT</small><strong><?= htmlspecialchars($order['payment_method'] === 'cod' ? 'Cash on delivery' : ucfirst(str_replace('_', ' ', (string) $order['payment_status']))) ?></strong></div><div><small>SHIPMENT</small><strong><?= $accountTrackingReference ? htmlspecialchars(($order['courier_name'] ?: 'Courier') . ' · ' . $accountTrackingReference) : htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $order['shipment_status']))) ?></strong></div></div>
                        <div class="account-order-progress"><span style="width:<?= $progress ?>%"></span><i class="is-active"></i><i class="<?= $progress >= 30 ? 'is-active' : '' ?>"></i><i class="<?= $progress >= 52 ? 'is-active' : '' ?>"></i><i class="<?= $progress >= 76 ? 'is-active' : '' ?>"></i><i class="<?= $progress >= 100 ? 'is-active' : '' ?>"></i></div>
                        <div class="account-order-card__foot"><p><i class="ph ph-map-pin"></i><?= htmlspecialchars($order['city'] . ', ' . $order['state'] . ' · ' . $order['pincode']) ?></p><div><?php if ($accountTrackingUrl): ?><a class="button button--secondary" href="<?= htmlspecialchars($accountTrackingUrl) ?>" target="_blank" rel="noopener">Track shipment <i class="ph ph-arrow-up-right"></i></a><?php endif; ?><a class="button button--cream" href="account-order.php?order=<?= rawurlencode((string) $order['order_number']) ?>">View order <i class="ph ph-arrow-right"></i></a></div></div>
                    </article>
                <?php endforeach; ?>
            </div><?php else: ?><div class="account-empty"><i class="ph ph-package"></i><span class="eyebrow">No orders yet</span><h3>Your first order will appear here.</h3><p>Stay signed in during checkout and we’ll connect the complete journey automatically.</p><a class="button button--primary" href="index.php#shop">Explore all products</a></div><?php endif; ?>
        </section>

        <section class="account-profile" id="profile">
            <div class="account-section-heading"><div><span class="eyebrow">Account settings</span><h2>Delivery details & <em>security.</em></h2></div></div>
            <div class="account-profile__grid">
                <form method="post" class="account-panel account-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="profile"><div class="account-panel__head"><i class="ph ph-map-pin-line"></i><div><h3>Profile & default address</h3><p>We’ll prefill these details at checkout.</p></div></div><div class="account-form__grid"><label><span>Full name</span><input name="name" value="<?= htmlspecialchars($customer['name']) ?>" required></label><label><span>Email address</span><input value="<?= htmlspecialchars($customer['email']) ?>" disabled></label><label><span>Phone</span><input name="phone" value="<?= htmlspecialchars($customer['phone']) ?>"></label><label><span>Pincode</span><input name="pincode" maxlength="6" inputmode="numeric" value="<?= htmlspecialchars($customer['pincode']) ?>"></label><label class="account-form__wide"><span>Address line 1</span><input name="address1" value="<?= htmlspecialchars($customer['address1']) ?>"></label><label class="account-form__wide"><span>Address line 2</span><input name="address2" value="<?= htmlspecialchars($customer['address2']) ?>"></label><label><span>City</span><input name="city" value="<?= htmlspecialchars($customer['city']) ?>"></label><label><span>State</span><input name="state" value="<?= htmlspecialchars($customer['state']) ?>"></label><label class="account-form__wide" style="display:flex;align-items:flex-start;gap:10px"><input type="checkbox" name="whatsapp_marketing_opt_in" value="1" style="width:18px;height:18px;margin-top:2px;accent-color:#087b50" <?= !empty($customer['whatsapp_marketing_opt_in']) && empty($customer['whatsapp_opt_out_at']) ? 'checked' : '' ?>><span><strong>WhatsApp offers & product news</strong><small style="display:block">Optional. Untick to withdraw consent, or reply STOP to any marketing message.</small></span></label></div><button class="button button--primary" type="submit">Save delivery profile</button></form>
                <form method="post" class="account-panel account-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="password"><div class="account-panel__head"><i class="ph ph-shield-check"></i><div><h3>Password & security</h3><p>Choose a password unique to Gawdee.</p></div></div><label><span>Current password</span><input type="password" name="current_password" autocomplete="current-password" required></label><label><span>New password</span><input type="password" name="new_password" autocomplete="new-password" required></label><label><span>Confirm new password</span><input type="password" name="new_password_confirmation" autocomplete="new-password" required></label><small>Use at least 8 characters with a letter and a number.</small><button class="button button--cream" type="submit">Update password</button><div class="account-security-note"><i class="ph ph-lock-key"></i><p><strong>Protected by secure hashing</strong>Your password is never stored in readable form.</p></div></form>
            </div>
        </section>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
