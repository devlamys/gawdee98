<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/integrations.php';

try {
    $configuredToken = gawdee_setting('ai_cron_token');
    $providedToken = (string) ($_GET['token'] ?? ($_SERVER['HTTP_X_GAWDEE_CRON_TOKEN'] ?? ''));
    if ($configuredToken === '' || !hash_equals($configuredToken, $providedToken)) {
        gawdee_json_response(['ok' => false, 'message' => 'Unauthorized.'], 401);
    }
    if (gawdee_setting('ai_auto_blog_enabled') !== '1') {
        gawdee_json_response(['ok' => true, 'status' => 'disabled']);
    }
    $last = strtotime(gawdee_setting('ai_last_blog_at')) ?: 0;
    $frequency = max(1, (int) gawdee_setting('ai_blog_frequency_days', '7')) * 86400;
    if ($last > time() - $frequency) {
        gawdee_json_response(['ok' => true, 'status' => 'not_due', 'next_run_after' => date(DATE_ATOM, $last + $frequency)]);
    }
    $topics = array_values(array_filter(array_map('trim', explode(',', gawdee_setting('ai_blog_topics')))));
    $topic = $topics ? $topics[array_rand($topics)] : 'mindful everyday Indian food choices';
    $post = gawdee_generate_blog($topic, 'published');
    gawdee_json_response(['ok' => true, 'status' => 'published', 'post' => $post]);
} catch (Throwable $exception) {
    gawdee_log_integration('ai', 'auto_blog', 'failed', $exception->getMessage());
    gawdee_json_response(['ok' => false, 'message' => $exception->getMessage()], 500);
}
