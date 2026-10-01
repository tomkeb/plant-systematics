<?php

declare(strict_types=1);

// Public UI text, merged into the main config by config.php: UI strings per language ("messages")
// and the hand-written navigation ("menu"; route is relative to the language prefix).
return [
    'messages' => [
        'cs' => [
            'siteName' => 'Název webu',
            'newsTitle' => 'Aktuality',
            'thesesTitle' => 'Závěrečné práce',
            'galleryTitle' => 'Galerie',
            'readMore' => 'Více',
            'back' => 'Zpět',
            'noItems' => 'Zatím zde nic není.',
            'supervisor' => 'Vedoucí',
            'allCategories' => 'Vše',
            'prev' => 'Předchozí',
            'next' => 'Další',
            'notFound' => 'Stránka nenalezena',
            'bachelor' => 'Bakalářské práce',
            'master' => 'Diplomové práce',
            'both' => 'Bakalářské i diplomové práce',
            'otherLanguage' => 'English',
        ],
        'en' => [
            'siteName' => 'Site name',
            'newsTitle' => 'News',
            'thesesTitle' => 'Theses',
            'galleryTitle' => 'Gallery',
            'readMore' => 'More',
            'back' => 'Back',
            'noItems' => 'Nothing here yet.',
            'supervisor' => 'Supervisor',
            'allCategories' => 'All',
            'prev' => 'Previous',
            'next' => 'Next',
            'notFound' => 'Page not found',
            'bachelor' => 'Bachelor theses',
            'master' => 'Master theses',
            'both' => 'Bachelor and master theses',
            'otherLanguage' => 'Česky',
        ],
    ],

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
];
