<?php

declare(strict_types=1);

require __DIR__ . '/includes/platform.php';

unset($_SESSION['customer_user_id']);
session_regenerate_id(true);
header('Location: index.php');
exit;
