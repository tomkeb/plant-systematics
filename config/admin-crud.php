<?php

declare(strict_types=1);

use App\Repository\NewsRepository;
use App\Repository\PageRepository;
use App\Repository\PhotoCategoryRepository;
use App\Repository\PhotoRepository;
use App\Repository\ThesisRepository;
use App\Repository\UserRepository;

// Admin CRUD definitions, one per entity: what is listed ("columns") and every stored field ("fields").
// Every row also has an "_id" assigned by SleekDB. A field has a "name" and a "type":
//   string, text (multi-line plain text), markdown, email, date (Y-m-d), bool, password (virtual, handled by CrudService),
//   enum ("options": value => label),
//   ref / refs ("ref": [repository class, label field or list of fields]; stored as the int _id of the
//   referenced row, or as a list of them).
// Other keys: "label" (a field without one gets no form input, e.g. values set by the upload or computed);
// "required"; "default" (bool); "auto" ("creator": set to the logged-in user's id on create, "editor": set on every
// create and edit).
return [
    'pages' => [
        'title' => 'Static pages',
        'repository' => PageRepository::class,
        'orderBy' => ['slug' => 'asc'],
        'columns' => [['slug', 'Slug'], ['name', 'Name'], ['date', 'Date']],
        'fields' => [
            ['name' => 'slug', 'label' => 'Slug (URL id, e.g. home, people, research, courses)', 'type' => 'string', 'required' => true],
            ['name' => 'name', 'label' => 'Name', 'type' => 'string', 'required' => true],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'string'],
            ['name' => 'text', 'label' => 'Text (Markdown)', 'type' => 'markdown'],
            ['name' => 'textEn', 'label' => 'Text (English, Markdown)', 'type' => 'markdown'],
            ['name' => 'authorId', 'type' => 'ref', 'ref' => [UserRepository::class, ['surname', 'name']], 'auto' => 'creator'],
            ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
        ],
    ],
    'news' => [
        'title' => 'News',
        'repository' => NewsRepository::class,
        'orderBy' => ['date' => 'desc'],
        'columns' => [['name', 'Name'], ['date', 'Date']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'string', 'required' => true],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'string'],
            ['name' => 'text', 'label' => 'Text (Markdown)', 'type' => 'markdown'],
            ['name' => 'textEn', 'label' => 'Text (English, Markdown)', 'type' => 'markdown'],
            ['name' => 'authorId', 'type' => 'ref', 'ref' => [UserRepository::class, ['surname', 'name']], 'auto' => 'creator'],
            ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
        ],
    ],
    'theses' => [
        'title' => 'Theses',
        'repository' => ThesisRepository::class,
        'orderBy' => ['name' => 'asc'],
        'columns' => [['name', 'Name'], ['category', 'Category'], ['supervisor', 'Supervisor'], ['visible', 'Visible']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'string', 'required' => true],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'string'],
            ['name' => 'desc', 'label' => 'Description (Markdown)', 'type' => 'markdown'],
            ['name' => 'descEn', 'label' => 'Description (English, Markdown)', 'type' => 'markdown'],
            [
                'name' => 'category',
                'label' => 'Category',
                'type' => 'enum',
                'required' => true,
                'options' => ['bc' => 'Bachelor', 'mgr' => 'Master', 'both' => 'Both'],
            ],
            ['name' => 'supervisor', 'label' => 'Supervisor', 'type' => 'ref', 'ref' => [UserRepository::class, ['surname', 'name']]],
            ['name' => 'visible', 'label' => 'Visible (untick to archive)', 'type' => 'bool', 'default' => true],
        ],
    ],
    'photo-categories' => [
        'title' => 'Photo categories',
        'repository' => PhotoCategoryRepository::class,
        'orderBy' => ['name' => 'asc'],
        'columns' => [['name', 'Name'], ['nameEn', 'Name (English)']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'string', 'required' => true],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'string'],
        ],
    ],
    'photos' => [
        'title' => 'Photos',
        'repository' => PhotoRepository::class,
        'orderBy' => ['date' => 'desc'],
        'canCreate' => false, // photos are created through the upload form
        'columns' => [['_id', 'ID'], ['filename', 'Photo', 'image'], ['name', 'Name'], ['author', 'Author'], ['categoryIds', 'Categories'], ['date', 'Date'], ['visible', 'Visible']],
        'fields' => [
            ['name' => 'filename', 'type' => 'string', 'required' => true], // set by the upload: lowercase, no diacritics, same in every size directory
            ['name' => 'name', 'label' => 'Name', 'type' => 'string'],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'string'],
            ['name' => 'desc', 'label' => 'Description (Markdown)', 'type' => 'markdown'],
            ['name' => 'descEn', 'label' => 'Description (English, Markdown)', 'type' => 'markdown'],
            ['name' => 'author', 'label' => 'Author', 'type' => 'string'], // free text, e.g. the photographer
            ['name' => 'categoryIds', 'label' => 'Categories', 'type' => 'refs', 'ref' => [PhotoCategoryRepository::class, 'name']],
            ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
            ['name' => 'visible', 'label' => 'Visible in gallery', 'type' => 'bool'],
            ['name' => 'lastEditBy', 'type' => 'ref', 'ref' => [UserRepository::class, ['surname', 'name']], 'auto' => 'editor'],
        ],
    ],
    'users' => [
        'title' => 'Users',
        'repository' => UserRepository::class,
        'orderBy' => ['surname' => 'asc'],
        'columns' => [['surname', 'Surname'], ['name', 'Name'], ['email', 'Email'], ['adminRights', 'Admin']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'string', 'required' => true],
            ['name' => 'surname', 'label' => 'Surname', 'type' => 'string', 'required' => true],
            ['name' => 'email', 'label' => 'Email (login)', 'type' => 'email', 'required' => true],
            ['name' => 'password', 'label' => 'Password (leave empty to keep the current one)', 'type' => 'password'],
            ['name' => 'passwordHash', 'type' => 'string', 'required' => true], // set from "password"
            ['name' => 'registered', 'label' => 'Registered', 'type' => 'date', 'required' => true],
            ['name' => 'adminRights', 'label' => 'Admin rights', 'type' => 'bool'],
        ],
    ],
];
