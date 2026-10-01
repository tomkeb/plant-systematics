<?php

declare(strict_types=1);

$root = dirname(__DIR__);

return [
    'paths' => [
        'root' => $root,
        'data' => $root . '/data',
        'var' => $root . '/var',
        'templates' => $root . '/templates',
        'uploads' => $root . '/public/uploads/photos',
    ],
    'uploadsUrl' => '/uploads/photos',

    'locales' => ['cs', 'en'],
    'defaultLocale' => 'cs',

    'perPage' => ['news' => 10, 'gallery' => 24],

    'photos' => [
        'maxBytes' => 15 * 1024 * 1024,
        'allowedMime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'sizes' => ['thumb' => 400, 'medium' => 1200, 'orig' => 2400],
        'jpegQuality' => 85,
    ],

    'login' => ['maxFailures' => 5, 'windowSeconds' => 900],

    // 'messages' and 'menu'
    ...require __DIR__ . '/translations-menu.php',
];
