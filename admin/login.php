<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/platform.php';

if (gawdee_admin()) {
    header('Location: index.php');
    exit;
}

$isSetup = !gawdee_has_admin();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        gawdee_verify_csrf($_POST['csrf_token'] ?? null);
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid email address.');
        }

        if ($isSetup) {
            $name = trim((string) ($_POST['name'] ?? ''));
            $confirm = (string) ($_POST['password_confirm'] ?? '');
            if (mb_strlen($name) < 2) {
                throw new RuntimeException('Enter the administrator name.');
            }
            if (strlen($password) < 12) {
                throw new RuntimeException('Use at least 12 characters for the password.');
            }
            if (!hash_equals($password, $confirm)) {
                throw new RuntimeException('The password confirmation does not match.');
            }
            $statement = gawdee_db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'admin']);
            session_regenerate_id(true);
            $_SESSION['admin_user_id'] = (int) gawdee_db()->lastInsertId();
        } else {
            $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
            if ($lockedUntil > time()) {
                throw new RuntimeException('Too many attempts. Try again in ' . ($lockedUntil - time()) . ' seconds.');
            }
            $statement = gawdee_db()->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $statement->execute([$email]);
            $user = $statement->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['login_locked_until'] = time() + 60;
                    $_SESSION['login_attempts'] = 0;
                }
                throw new RuntimeException('Email or password is incorrect.');
            }
            session_regenerate_id(true);
            $_SESSION['admin_user_id'] = (int) $user['id'];
            unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
            gawdee_db()->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$user['id']]);
        }

        header('Location: index.php');
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#073c2b">
    <title><?= $isSetup ? 'Create Gawdee admin' : 'Gawdee admin login' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-auth">
<main class="auth-shell">
    <section class="auth-visual">
        <a href="../index.php" class="auth-brand"><img src="../assets/images/logo.png" alt="Gawdee"></a>
        <div class="auth-visual__copy">
            <span><i class="ph ph-sparkle"></i> Commerce control centre</span>
            <h1>Good food.<br><em>Beautifully managed.</em></h1>
            <p>Manage the storefront, orders, payments, courier workflow and AI publishing from one calm workspace.</p>
        </div>
        <div class="auth-orbit auth-orbit--one"></div><div class="auth-orbit auth-orbit--two"></div>
        <div class="auth-visual__stats"><strong>One dashboard</strong><span>CMS · Commerce · AI</span></div>
    </section>
    <section class="auth-panel">
        <div class="auth-card">
            <span class="auth-kicker"><?= $isSetup ? 'First-time setup' : 'Welcome back' ?></span>
            <h2><?= $isSetup ? 'Create your administrator' : 'Sign in to Gawdee' ?></h2>
            <p><?= $isSetup ? 'No default credentials are used. Create the first secure administrator now.' : 'Use your administrator account to continue.' ?></p>
            <?php if ($error): ?><div class="admin-alert admin-alert--error"><i class="ph ph-warning-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post" class="admin-form auth-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                <?php if ($isSetup): ?>
                    <label><span>Full name</span><div class="field-icon"><i class="ph ph-user"></i><input name="name" autocomplete="name" required value="<?= htmlspecialchars((string) ($_POST['name'] ?? '')) ?>" placeholder="Store administrator"></div></label>
                <?php endif; ?>
                <label><span>Email address</span><div class="field-icon"><i class="ph ph-envelope-simple"></i><input type="email" name="email" autocomplete="email" required value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>" placeholder="you@gawdee.com"></div></label>
                <label><span>Password</span><div class="field-icon"><i class="ph ph-lock-key"></i><input type="password" name="password" autocomplete="<?= $isSetup ? 'new-password' : 'current-password' ?>" required minlength="<?= $isSetup ? '12' : '1' ?>" placeholder="<?= $isSetup ? 'Minimum 12 characters' : 'Your password' ?>"></div></label>
                <?php if ($isSetup): ?>
                    <label><span>Confirm password</span><div class="field-icon"><i class="ph ph-shield-check"></i><input type="password" name="password_confirm" autocomplete="new-password" required minlength="12" placeholder="Repeat the password"></div></label>
                <?php endif; ?>
                <button class="admin-button admin-button--primary admin-button--full" type="submit"><?= $isSetup ? 'Create admin & continue' : 'Sign in' ?> <i class="ph ph-arrow-right"></i></button>
            </form>
            <a class="auth-back" href="../index.php"><i class="ph ph-arrow-left"></i> Back to storefront</a>
        </div>
    </section>
</main>
</body>
</html>
