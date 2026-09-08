<?php

declare(strict_types=1);

require __DIR__ . '/includes/integrations.php';
require_once __DIR__ . '/includes/wishlist.php';

$returnTo = gawdee_safe_return_path((string) ($_GET['return'] ?? $_POST['return'] ?? 'account.php'));
if (gawdee_customer()) {
    header('Location: ' . $returnTo);
    exit;
}

$error = '';
$notice = '';
$loginMode = (string) ($_POST['login_mode'] ?? $_GET['method'] ?? 'password');
$loginMode = $loginMode === 'otp' ? 'otp' : 'password';
$otpPending = isset($_POST['otp_request']) || isset($_POST['otp_verify']);
$completeLogin = static function (array $customer, bool $remember, string $returnTo): never {
    session_regenerate_id(true);
    $_SESSION['customer_user_id'] = (int) $customer['id'];
    gawdee_wishlist_merge_guest();
    $_SESSION['customer_login_attempts'] = [];
    if ($remember) {
        setcookie(session_name(), session_id(), [
            'expires' => time() + 2592000,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    gawdee_db()->prepare('UPDATE users SET last_login_at=CURRENT_TIMESTAMP WHERE id=?')->execute([(int) $customer['id']]);
    header('Location: ' . $returnTo);
    exit;
};
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        gawdee_verify_csrf($_POST['csrf_token'] ?? null);
        if ($loginMode === 'otp') {
            $identity = trim((string) ($_POST['identity'] ?? ''));
            if (mb_strlen($identity) < 5) {
                throw new RuntimeException('Enter your registered email address or phone number.');
            }
            if (isset($_POST['otp_request'])) {
                gawdee_whatsapp_request_otp($identity, (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
                $otpPending = true;
                $notice = 'If that account exists, a six-digit code was sent to its registered WhatsApp number. It expires in 10 minutes.';
            } elseif (isset($_POST['otp_verify'])) {
                $customer = gawdee_whatsapp_verify_otp($identity, trim((string) ($_POST['otp_code'] ?? '')));
                if (!$customer) {
                    throw new RuntimeException('That code is invalid or expired. Request a new WhatsApp OTP.');
                }
                $completeLogin($customer, !empty($_POST['remember']), $returnTo);
            }
        } else {
        $attempts = is_array($_SESSION['customer_login_attempts'] ?? null) ? $_SESSION['customer_login_attempts'] : [];
        $attempts = array_values(array_filter($attempts, static fn(int $timestamp): bool => $timestamp > time() - 900));
        if (count($attempts) >= 6) {
            throw new RuntimeException('Too many sign-in attempts. Please wait 15 minutes and try again.');
        }
        $identity = trim((string) ($_POST['identity'] ?? ''));
        $email = strtolower($identity);
        $phone = preg_replace('/\D+/', '', $identity) ?? '';
        $password = (string) ($_POST['password'] ?? '');
        $statement = gawdee_db()->prepare("SELECT * FROM users WHERE (LOWER(email) = ? OR (? <> '' AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') = ?)) AND role = 'customer'");
        $statement->execute([$email, $phone, $phone]);
        $customer = $statement->fetch();
        if (!$customer || !password_verify($password, (string) $customer['password_hash'])) {
            $attempts[] = time();
            $_SESSION['customer_login_attempts'] = $attempts;
            throw new RuntimeException('The email, phone number or password is incorrect.');
        }
        $completeLogin($customer, !empty($_POST['remember']), $returnTo);
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$pageTitle = 'Customer sign in | Gawdee';
$pageDescription = 'Sign in to view your Gawdee orders, delivery updates and saved products.';
$bodyClass = 'customer-auth-page customer-auth-page--reference';
require __DIR__ . '/includes/header.php';
?>
<section class="gawdee-login-shell">
    <div class="gawdee-login-story">
        <a class="gawdee-login-story__logo" href="index.php" aria-label="Return to Gawdee home"><img src="<?= htmlspecialchars($siteDesign['brand_logo']) ?>" alt="Gawdee — The Mother of Organic Nutrition"></a>
        <div class="gawdee-login-story__copy">
            <h1><?= htmlspecialchars($siteDesign['login_story_title']) ?></h1>
            <i aria-hidden="true"></i>
            <p><?= nl2br(htmlspecialchars($siteDesign['login_story_text'])) ?></p>
        </div>
        <div class="gawdee-login-story__promises" aria-label="Gawdee product promises">
            <?php foreach ([['ph-leaf','100%','Natural'],['ph-bowl-steam','Bilona','Method'],['ph-farm','Farm','Fresh'],['ph-shield-check','Trusted','Quality']] as $promise): ?>
                <article><span><i class="ph <?= $promise[0] ?>"></i></span><strong><?= $promise[1] ?><small><?= $promise[2] ?></small></strong></article>
            <?php endforeach; ?>
        </div>
        <p class="gawdee-login-story__motto">Good Food<br>Brighter Lives <i class="ph-fill ph-heart"></i></p>
        <p class="gawdee-login-story__footer"><span>PURE BY NATURE</span><i class="ph ph-leaf"></i><span>NOURISHING GENERATIONS</span></p>
    </div>

    <div class="gawdee-login-access">
        <p class="gawdee-login-access__eyebrow">NATURAL <b>•</b> HEALTHY <b>•</b> HAPPIER TOMORROWS</p>
        <p class="gawdee-login-access__note"><i class="ph ph-leaf"></i>A Healthier<br>Tomorrow</p>

        <div class="gawdee-login-card">
            <a class="gawdee-login-card__logo" href="index.php"><img src="<?= htmlspecialchars($siteDesign['brand_logo']) ?>" alt="Gawdee"></a>
            <h2><?= htmlspecialchars($siteDesign['login_title']) ?></h2>
            <p class="gawdee-login-card__intro"><?= htmlspecialchars($siteDesign['login_intro']) ?></p>

            <div class="gawdee-login-methods" role="tablist" aria-label="Login method">
                <button type="button" class="<?= $loginMode === 'password' ? 'is-active' : '' ?>" role="tab" aria-selected="<?= $loginMode === 'password' ? 'true' : 'false' ?>" data-login-tab="password">Email / Phone</button>
                <button type="button" class="<?= $loginMode === 'otp' ? 'is-active' : '' ?>" role="tab" aria-selected="<?= $loginMode === 'otp' ? 'true' : 'false' ?>" data-login-tab="otp">Continue with OTP</button>
            </div>

            <?php if ($error): ?><div class="gawdee-login-alert"><i class="ph ph-warning-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($notice): ?><div class="gawdee-login-alert" style="color:#05633f;background:#edf9f2;border-color:#b9e3ca"><i class="ph ph-check-circle"></i><?= htmlspecialchars($notice) ?></div><?php endif; ?>

            <form method="post" class="gawdee-login-form" data-login-form="password" <?= $loginMode === 'password' ? '' : 'hidden' ?>>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                <input type="hidden" name="return" value="<?= htmlspecialchars($returnTo) ?>">
                <input type="hidden" name="login_mode" value="password">
                <label class="gawdee-login-field"><span class="sr-only">Email address or phone number</span><i class="ph ph-envelope-simple"></i><input type="text" name="identity" autocomplete="username" required value="<?= htmlspecialchars((string) ($_POST['identity'] ?? '')) ?>" placeholder="Enter your email or phone number"></label>
                <label class="gawdee-login-field"><span class="sr-only">Password</span><i class="ph ph-lock-key"></i><input type="password" name="password" autocomplete="current-password" required placeholder="Enter your password"><button type="button" data-password-toggle aria-label="Show password"><i class="ph ph-eye"></i></button></label>
                <div class="gawdee-login-options"><label><input type="checkbox" name="remember" value="1" <?= !empty($_POST['remember']) ? 'checked' : '' ?>><span><i class="ph ph-check"></i></span>Remember me</label><a href="mailto:<?= htmlspecialchars(gawdee_setting('store_email', 'info@gawdee.com')) ?>?subject=Gawdee%20password%20help">Need password help?</a></div>
                <button class="gawdee-login-submit" type="submit">Login <i class="ph ph-arrow-right"></i></button>
            </form>

            <form method="post" class="gawdee-login-form" data-login-form="otp" <?= $loginMode === 'otp' ? '' : 'hidden' ?>>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="return" value="<?= htmlspecialchars($returnTo) ?>"><input type="hidden" name="login_mode" value="otp">
                <label class="gawdee-login-field"><span class="sr-only">Registered email address or phone number</span><i class="ph ph-whatsapp-logo"></i><input type="text" name="identity" autocomplete="username" required value="<?= htmlspecialchars((string) ($_POST['identity'] ?? '')) ?>" placeholder="Registered email or WhatsApp number"></label>
                <?php if ($otpPending): ?><label class="gawdee-login-field"><span class="sr-only">Six-digit OTP</span><i class="ph ph-password"></i><input type="text" name="otp_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="Enter 6-digit OTP"></label><?php endif; ?>
                <div class="gawdee-login-options"><label><input type="checkbox" name="remember" value="1"><span><i class="ph ph-check"></i></span>Remember me</label><?php if ($otpPending): ?><button type="submit" name="otp_request" value="1" style="border:0;background:none;color:#087b50;text-decoration:underline;cursor:pointer">Send a new code</button><?php endif; ?></div>
                <button class="gawdee-login-submit" type="submit" name="<?= $otpPending ? 'otp_verify' : 'otp_request' ?>" value="1"><?= $otpPending ? 'Verify & Login' : 'Send WhatsApp OTP' ?> <i class="ph ph-arrow-right"></i></button>
            </form>

            <p class="gawdee-login-switch">Don’t have an account? <a href="register.php?return=<?= rawurlencode($returnTo) ?>">Create Account</a></p>
            <div class="gawdee-login-trust" aria-label="Account promises"><article><span><i class="ph ph-shield-check"></i></span><strong>Secure<small>Login</small></strong></article><article><span><i class="ph ph-truck"></i></span><strong>Fast<small>Delivery</small></strong></article><article><span><i class="ph ph-leaf"></i></span><strong>Pure<small>Products</small></strong></article></div>
        </div>
    </div>
</section>
<script>
document.querySelectorAll('[data-login-tab]').forEach((button) => button.addEventListener('click', () => {
    const mode = button.dataset.loginTab;
    document.querySelectorAll('[data-login-tab]').forEach((item) => { item.classList.toggle('is-active', item === button); item.setAttribute('aria-selected', item === button ? 'true' : 'false'); });
    document.querySelectorAll('[data-login-form]').forEach((form) => { form.hidden = form.dataset.loginForm !== mode; });
}));
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
