<?php

declare(strict_types=1);

require __DIR__ . '/includes/platform.php';
require_once __DIR__ . '/includes/wishlist.php';

$returnTo = gawdee_safe_return_path((string) ($_GET['return'] ?? $_POST['return'] ?? 'account.php'));
if (gawdee_customer()) {
    header('Location: ' . $returnTo);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        gawdee_verify_csrf($_POST['csrf_token'] ?? null);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            throw new RuntimeException('Enter your full name.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid email address.');
        }
        if (!preg_match('/^[0-9+()\s-]{8,18}$/', $phone)) {
            throw new RuntimeException('Enter a valid phone number.');
        }
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new RuntimeException('Use at least 8 characters with a letter and a number.');
        }
        if (!hash_equals($password, $confirmation)) {
            throw new RuntimeException('The password confirmation does not match.');
        }
        $db = gawdee_db();
        $db->beginTransaction();
        $marketingOptIn = isset($_POST['whatsapp_marketing_opt_in']) ? 1 : 0;
        $statement = $db->prepare("INSERT INTO users (name, email, password_hash, role, phone, whatsapp_marketing_opt_in, whatsapp_marketing_opt_in_at) VALUES (?, ?, ?, 'customer', ?, ?, CASE WHEN ?=1 THEN CURRENT_TIMESTAMP ELSE NULL END)");
        $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $marketingOptIn, $marketingOptIn]);
        $userId = (int) $db->lastInsertId();
        $lastOrderNumber = trim((string) ($_SESSION['last_order_number'] ?? ''));
        if ($lastOrderNumber !== '') {
            $db->prepare('UPDATE orders SET user_id=? WHERE user_id IS NULL AND order_number=? AND lower(email)=lower(?)')->execute([$userId, $lastOrderNumber, $email]);
        }
        $db->commit();
        session_regenerate_id(true);
        $_SESSION['customer_user_id'] = $userId;
        gawdee_wishlist_merge_guest();
        header('Location: ' . $returnTo);
        exit;
    } catch (PDOException $exception) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        $error = str_contains(strtolower($exception->getMessage()), 'unique') ? 'An account already exists for this email. Sign in instead.' : 'Unable to create your account right now.';
    } catch (Throwable $exception) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        $error = $exception->getMessage();
    }
}

$pageTitle = 'Create customer account | Gawdee';
$pageDescription = 'Create a Gawdee account for order history and tracked delivery updates.';
$bodyClass = 'customer-auth-page';
require __DIR__ . '/includes/header.php';
?>
<section class="customer-auth-shell customer-auth-shell--register">
    <div class="customer-auth-visual">
        <span class="customer-auth-visual__label"><i class="ph ph-sparkle"></i> A simpler shopping journey</span>
        <div><span class="eyebrow eyebrow--light">Join the Gawdee family</span><h1>Every order.<br><em>One calm place.</em></h1><p>Create an account before checkout and every new order will appear automatically with payment, packing and delivery progress.</p></div>
        <div class="customer-auth-steps"><span><i class="ph ph-receipt"></i> Complete history</span><span><i class="ph ph-bell"></i> Status updates</span><span><i class="ph ph-map-pin"></i> DTDC tracking</span></div>
    </div>
    <div class="customer-auth-panel">
        <div class="customer-auth-card">
            <a class="customer-auth-back" href="index.php"><i class="ph ph-arrow-left"></i> Back to shop</a>
            <span class="eyebrow">Create account</span>
            <h2>Start your order dashboard</h2>
            <p>Your account will securely connect future orders placed while signed in.</p>
            <?php if ($error): ?><div class="customer-auth-alert"><i class="ph ph-warning-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post" class="customer-auth-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="return" value="<?= htmlspecialchars($returnTo) ?>">
                <div class="customer-auth-form__row"><label><span>Full name</span><div><i class="ph ph-user"></i><input name="name" autocomplete="name" required value="<?= htmlspecialchars((string) ($_POST['name'] ?? '')) ?>"></div></label><label><span>Phone</span><div><i class="ph ph-phone"></i><input type="tel" name="phone" autocomplete="tel" required value="<?= htmlspecialchars((string) ($_POST['phone'] ?? '')) ?>"></div></label></div>
                <label><span>Email address</span><div><i class="ph ph-envelope"></i><input type="email" name="email" autocomplete="email" required value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>"></div></label>
                <div class="customer-auth-form__row"><label><span>Password</span><div><i class="ph ph-lock-key"></i><input type="password" name="password" autocomplete="new-password" required><button type="button" data-password-toggle aria-label="Show password"><i class="ph ph-eye"></i></button></div></label><label><span>Confirm password</span><div><i class="ph ph-lock-key"></i><input type="password" name="password_confirmation" autocomplete="new-password" required></div></label></div>
                <small class="customer-password-help">At least 8 characters, including a letter and a number.</small>
                <label style="display:flex;align-items:flex-start;gap:10px;color:#52635d;font-size:.82rem;line-height:1.45"><input type="checkbox" name="whatsapp_marketing_opt_in" value="1" style="width:18px;height:18px;margin-top:2px;accent-color:#087b50" <?= isset($_POST['whatsapp_marketing_opt_in']) ? 'checked' : '' ?>><span>Send me occasional Gawdee offers and product news on WhatsApp. Optional; reply STOP at any time.</span></label>
                <button class="button button--primary" type="submit">Create my account <i class="ph ph-arrow-right"></i></button>
            </form>
            <p class="customer-auth-switch">Already registered? <a href="login.php?return=<?= rawurlencode($returnTo) ?>">Sign in here</a></p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
