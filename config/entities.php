<?php

declare(strict_types=1);

use App\Repository\PageRepository;
use App\Repository\PhotoCategoryRepository;
use App\Repository\PhotoRepository;
use App\Repository\NewsRepository;
use App\Repository\ThesisRepository;
use App\Repository\UserRepository;

// Admin CRUD definitions. Field types: text, email, textarea, html, date, select, checkbox, password, author.
// "author" is never shown; it is set to the logged-in user on create. "select" takes "options"
// (value => label) or "optionsFrom" ([repository class, label field]).
return [
    'pages' => [
        'title' => 'Static pages',
        'repository' => PageRepository::class,
        'orderBy' => ['slug' => 'asc'],
        'columns' => [['slug', 'Slug'], ['name', 'Name'], ['date', 'Date']],
        'fields' => [
            ['name' => 'slug', 'label' => 'Slug (URL id, e.g. home, people, research, courses)', 'type' => 'text', 'required' => true],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'text'],
            ['name' => 'text', 'label' => 'Text (HTML)', 'type' => 'html'],
            ['name' => 'textEn', 'label' => 'Text (English, HTML)', 'type' => 'html'],
            ['name' => 'authorId', 'type' => 'author'],
            ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
        ],
    ],
    'news' => [
        'title' => 'News',
        'repository' => NewsRepository::class,
        'orderBy' => ['date' => 'desc'],
        'columns' => [['name', 'Name'], ['date', 'Date']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'text'],
            ['name' => 'text', 'label' => 'Text (HTML)', 'type' => 'html'],
            ['name' => 'textEn', 'label' => 'Text (English, HTML)', 'type' => 'html'],
            ['name' => 'authorId', 'type' => 'author'],
            ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
        ],
    ],
    'theses' => [
        'title' => 'Theses',
        'repository' => ThesisRepository::class,
        'orderBy' => ['name' => 'asc'],
        'columns' => [['name', 'Name'], ['category', 'Category']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'text'],
            ['name' => 'desc', 'label' => 'Description (HTML)', 'type' => 'html'],
            ['name' => 'descEn', 'label' => 'Description (English, HTML)', 'type' => 'html'],
            [
                'name' => 'category',
                'label' => 'Category',
                'type' => 'select',
                'required' => true,
                'options' => ['bc' => 'Bachelor', 'mgr' => 'Master', 'both' => 'Both'],
            ],
        ],
    ],
    'photo-categories' => [
        'title' => 'Photo categories',
        'repository' => PhotoCategoryRepository::class,
        'orderBy' => ['name' => 'asc'],
        'columns' => [['name', 'Name'], ['nameEn', 'Name (English)']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'text'],
        ],
    ],
    'photos' => [
        'title' => 'Photos',
        'repository' => PhotoRepository::class,
        'orderBy' => ['date' => 'desc'],
        'canCreate' => false, // photos are created through the upload form
        'columns' => [['filename', 'Photo', 'image'], ['name', 'Name'], ['date', 'Date'], ['visible', 'Visible']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'text'],
            ['name' => 'desc', 'label' => 'Description', 'type' => 'textarea'],
            ['name' => 'descEn', 'label' => 'Description (English)', 'type' => 'textarea'],
            [
                'name' => 'categoryId',
                'label' => 'Category',
                'type' => 'select',
                'cast' => 'int',
                'optionsFrom' => [PhotoCategoryRepository::class, 'name'],
            ],
            ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
            ['name' => 'visible', 'label' => 'Visible in gallery', 'type' => 'checkbox'],
        ],
    ],
    'users' => [
        'title' => 'Users',
        'repository' => UserRepository::class,
        'orderBy' => ['surname' => 'asc'],
        'columns' => [['surname', 'Surname'], ['name', 'Name'], ['email', 'Email'], ['adminRights', 'Admin']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'surname', 'label' => 'Surname', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'Email (login)', 'type' => 'email', 'required' => true],
            ['name' => 'password', 'label' => 'Password (leave empty to keep the current one)', 'type' => 'password'],
            ['name' => 'registered', 'label' => 'Registered', 'type' => 'date', 'required' => true],
            ['name' => 'adminRights', 'label' => 'Admin rights', 'type' => 'checkbox'],
        ],
    ],
];
