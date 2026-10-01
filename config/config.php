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

    // Hand-written navigation: route is relative to the language prefix.
    'menu' => [
        ['route' => '/', 'label' => ['cs' => 'Domů', 'en' => 'Home']],
        ['route' => '/news', 'label' => ['cs' => 'Aktuality', 'en' => 'News']],
        ['route' => '/people', 'label' => ['cs' => 'Lidé', 'en' => 'People']],
        ['route' => '/research', 'label' => ['cs' => 'Výzkum', 'en' => 'Research']],
        ['route' => '/laboratory', 'label' => ['cs' => 'Laboratoř', 'en' => 'Laboratory']],
        ['route' => '/courses', 'label' => ['cs' => 'Výuka', 'en' => 'Courses']],
        ['route' => '/theses', 'label' => ['cs' => 'Závěrečné práce', 'en' => 'Theses']],
        ['route' => '/gallery', 'label' => ['cs' => 'Galerie', 'en' => 'Gallery']],
    ],

    'perPage' => ['news' => 10, 'gallery' => 24],

    'photos' => [
        'maxBytes' => 15 * 1024 * 1024,
        'allowedMime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'sizes' => ['thumb' => 400, 'medium' => 1200, 'orig' => 2400],
        'jpegQuality' => 85,
    ],

    'login' => ['maxFailures' => 5, 'windowSeconds' => 900],

    'messages' => require __DIR__ . '/messages.php',
];
