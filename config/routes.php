<?php

declare(strict_types=1);

use App\Controller\Admin\AuthController;
use App\Controller\Admin\CrudController;
use App\Controller\Admin\DashboardController;
use App\Controller\Admin\PhotoAdminController;
use App\Controller\Front\GalleryController;
use App\Controller\Front\NewsController;
use App\Controller\Front\PageController;
use App\Controller\Front\ThesisController;

// [method, pattern, [controller, action]]. First match wins. Public patterns are relative to the
// language prefix (/cs, /en). Admin patterns have no prefix.
return [
    // Admin: specific routes before the generic /admin/{entity} ones.
    ['GET', '/admin/login', [AuthController::class, 'loginForm']],
    ['POST', '/admin/login', [AuthController::class, 'login']],
    ['POST', '/admin/logout', [AuthController::class, 'logout']],
    ['GET', '/admin', [DashboardController::class, 'index']],
    ['GET', '/admin/photos/upload', [PhotoAdminController::class, 'uploadForm']],
    ['POST', '/admin/photos/upload', [PhotoAdminController::class, 'upload']],
    ['POST', '/admin/photos/{id}/delete', [PhotoAdminController::class, 'deletePhoto']],
    ['GET', '/admin/{entity}', [CrudController::class, 'list']],
    ['GET', '/admin/{entity}/new', [CrudController::class, 'createForm']],
    ['POST', '/admin/{entity}/new', [CrudController::class, 'create']],
    ['GET', '/admin/{entity}/{id}/edit', [CrudController::class, 'editForm']],
    ['POST', '/admin/{entity}/{id}/edit', [CrudController::class, 'update']],
    ['POST', '/admin/{entity}/{id}/delete', [CrudController::class, 'delete']],

    // Public
    ['GET', '/', [PageController::class, 'home']],
    ['GET', '/people', [PageController::class, 'people']],
    ['GET', '/research', [PageController::class, 'research']],
    ['GET', '/courses', [PageController::class, 'courses']],
    ['GET', '/page/{slug}', [PageController::class, 'page']],
    ['GET', '/news', [NewsController::class, 'index']],
    ['GET', '/news/{id}', [NewsController::class, 'detail']],
    ['GET', '/theses', [ThesisController::class, 'index']],
    ['GET', '/gallery', [GalleryController::class, 'index']],
];
