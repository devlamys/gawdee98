<?php

declare(strict_types=1);

$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (preg_match('#^/(?:storage|includes|tests|scripts)(?:/|$)#i', $uri) || preg_match('#/(?:\.|\.git)#', $uri)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    return true;
}

$path = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if ($uri !== '/' && is_file($path)) {
    return false;
}
if ($uri === '/') {
    require __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Not found';
return true;
