<?php

declare(strict_types=1);

/**
 * Router for `php spark serve`. Mirrors the framework rewrite router but serves
 * build assets with immutable cache headers, matching public/assets/.htaccess
 * which the PHP built-in server ignores (and drops from router-set headers).
 */

$uri = urldecode(
    parse_url('https://codeigniter.com' . $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '',
);

$_SERVER['SCRIPT_NAME'] = '/index.php';

$docroot = realpath($_SERVER['DOCUMENT_ROOT']);
$path    = $docroot . str_replace('/', DIRECTORY_SEPARATOR, $uri);

if ($uri !== '/' && is_dir($path)) {
    return false;
}

if ($uri !== '/' && is_file($path) && str_starts_with(realpath($path), $docroot)) {
    $mimeTypes = [
        'css'   => 'text/css; charset=UTF-8',
        'js'    => 'text/javascript; charset=UTF-8',
        'mjs'   => 'text/javascript; charset=UTF-8',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'otf'   => 'font/otf',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'png'   => 'image/png',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'avif'  => 'image/avif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
    ];

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    // Immutable cache only for build artifacts; service workers and other
    // root files keep default serving so they stay revalidatable.
    if (isset($mimeTypes[$ext]) && str_starts_with($uri, '/assets/')) {
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Content-Type: ' . $mimeTypes[$ext]);
        header('Content-Length: ' . filesize($path));
        http_response_code(200);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            readfile($path);
        }

        return true;
    }

    return false;
}

require_once $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';
