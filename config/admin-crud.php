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
//   string, text (multi-line plain text), markdown, email, int (whole number; "min", "max" optional), date (Y-m-d), bool, password and file (virtual: not stored, handled by CrudService / the controller),
//   enum ("options": value => label),
//   ref / refs ("ref": [repository class, label field or list of fields]; stored as the int _id of the
//   referenced row, or as a list of them).
// A column is [field, header] with an optional third item: "image" (the field holds a photo filename, shown as a
// thumbnail) or ["orig" => "filename"] (the cell links to the full-size photo named in that field).
// Entity "singular" and "about": a lowercase singular name and a plain-language sentence for the data model export (bin/export-data-model.php). Field "about":
// a note for the same export; "internal" leaves a field out of it.
// Other keys: "label" (a field without one gets no form input, e.g. values set by the upload or computed);
// "required"; "default" (bool); "auto" ("creator": set to the logged-in user's id on create, "editor": set on every
// create and edit).
return [
    'pages' => [
        'title' => 'Static pages',
        'singular' => 'static page',
        'about' => 'The fixed pages of the site (home, people, research, courses, laboratory, ...). Each page has its own web address and is written as formatted text.',
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
        'singular' => 'news item',
        'about' => 'News items, shown newest first, each with its own detail page.',
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
        'singular' => 'thesis',
        'about' => 'Offered bachelor and master thesis topics, shown on the site grouped by supervisor. Old topics can be archived (hidden) instead of deleted.',
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
                'options' => ['bachelor' => 'Bachelor', 'master' => 'Master', 'both' => 'Both'],
            ],
            ['name' => 'supervisor', 'label' => 'Supervisor', 'type' => 'ref', 'ref' => [UserRepository::class, ['surname', 'name']]],
            ['name' => 'fieldTripPercentage', 'label' => 'Field trip share (0-100)', 'type' => 'int', 'min' => 0, 'max' => 100],
            ['name' => 'visible', 'label' => 'Visible (untick to archive)', 'type' => 'bool', 'default' => true],
        ],
    ],
    'photo-categories' => [
        'title' => 'Photo categories',
        'singular' => 'photo category',
        'about' => 'The categories of the photo gallery. A photo can belong to several categories.',
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
        'singular' => 'photo',
        'about' => 'Gallery photos. Every upload is stored in three sizes (thumbnail, medium, original) and can later be replaced by a new file.',
        'repository' => PhotoRepository::class,
        'orderBy' => ['date' => 'desc'],
        'canCreate' => false, // photos are created through the upload form
        'columns' => [['_id', 'ID'], ['filename', 'Photo', 'image'], ['name', 'Name', ['orig' => 'filename']], ['author', 'Author'], ['categoryIds', 'Categories'], ['date', 'Date'], ['visible', 'Visible']],
        'fields' => [
            ['name' => 'filename', 'type' => 'string', 'required' => true, 'about' => 'The image file; set by the upload (lowercase, no diacritics).'],
            ['name' => 'name', 'label' => 'Name', 'type' => 'string'],
            ['name' => 'nameEn', 'label' => 'Name (English)', 'type' => 'string'],
            ['name' => 'desc', 'label' => 'Description (Markdown)', 'type' => 'markdown'],
            ['name' => 'descEn', 'label' => 'Description (English, Markdown)', 'type' => 'markdown'],
            ['name' => 'author', 'label' => 'Author', 'type' => 'string'], // free text, e.g. the photographer
            ['name' => 'categoryIds', 'label' => 'Categories', 'type' => 'refs', 'ref' => [PhotoCategoryRepository::class, 'name']],
            ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
            ['name' => 'visible', 'label' => 'Visible in gallery', 'type' => 'bool'],
            ['name' => 'file', 'label' => 'Replace the photo (optional)', 'type' => 'file'], // virtual: re-upload, see PhotoAdminController
            ['name' => 'lastEditBy', 'type' => 'ref', 'ref' => [UserRepository::class, ['surname', 'name']], 'auto' => 'editor'],
        ],
    ],
    'users' => [
        'title' => 'Users',
        'singular' => 'user',
        'about' => 'The people who can log in to the administration. They can also be chosen as thesis supervisors.',
        'repository' => UserRepository::class,
        'orderBy' => ['surname' => 'asc'],
        'columns' => [['surname', 'Surname'], ['name', 'Name'], ['email', 'Email'], ['adminRights', 'Admin']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'string', 'required' => true],
            ['name' => 'surname', 'label' => 'Surname', 'type' => 'string', 'required' => true],
            ['name' => 'email', 'label' => 'Email (login)', 'type' => 'email', 'required' => true],
            ['name' => 'password', 'label' => 'Password (leave empty to keep the current one)', 'type' => 'password'],
            ['name' => 'passwordHash', 'type' => 'string', 'required' => true, 'internal' => true], // set from "password"
            ['name' => 'registered', 'label' => 'Registered', 'type' => 'date', 'required' => true],
            ['name' => 'adminRights', 'label' => 'Admin rights', 'type' => 'bool'],
        ],
    ],
];
