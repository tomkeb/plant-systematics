<?php

declare(strict_types=1);

// Usage: php bin/seed.php   (inserts the static pages and sample categories if they do not exist yet)

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Config;
use App\Repository\PageRepository;
use App\Repository\PhotoCategoryRepository;

$config = new Config(require dirname(__DIR__) . '/config/config.php');
$pages = new PageRepository($config);
$categories = new PhotoCategoryRepository($config);

$titles = [
    'home' => ['Úvod', 'Home'],
    'people' => ['Lidé', 'People'],
    'research' => ['Výzkum', 'Research'],
    'courses' => ['Výuka', 'Courses'],
];
foreach ($titles as $slug => [$cs, $en]) {
    if ($pages->findOneBy(['slug', '=', $slug]) === null) {
        $pages->insert([
            'slug' => $slug,
            'name' => $cs,
            'nameEn' => $en,
            'text' => 'Obsah doplníte v administraci.',
            'textEn' => 'Fill in the content in the admin.',
            'authorId' => null,
            'date' => date('Y-m-d'),
        ]);
        echo "Page {$slug} created\n";
    }
}

if ($categories->count() === 0) {
    $categories->insert(['name' => 'Akce', 'nameEn' => 'Events']);
    echo "Sample photo category created\n";
}
