<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    require_once __DIR__ . '/includes/platform.php';
    $platformLoaded = true;
    $db = gawdee_db();
    $dbDriver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $dbSuccess = true;
    $errorMsg = null;
    $errorTrace = null;
} catch (Throwable $e) {
    $platformLoaded = false;
    $dbSuccess = false;
    $errorMsg = $e->getMessage();
    $errorTrace = $e->getTraceAsString();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gawdee Server Diagnostic Check</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h2>Gawdee Server Diagnostic Check</h2>
    <p>PHP Version: <?= PHP_VERSION ?></p>

    <?php if ($dbSuccess): ?>
        <p class="success">Platform loaded successfully.</p>
        <p class="success">Database Connection Success! Driver: <?= htmlspecialchars($dbDriver) ?></p>
    <?php else: ?>
        <p class="error">Database / Runtime Error: <?= htmlspecialchars($errorMsg) ?></p>
        <pre><?= htmlspecialchars($errorTrace) ?></pre>
    <?php endif; ?>
</body>
</html>
