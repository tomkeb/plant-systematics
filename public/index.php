<?php

declare(strict_types=1);

// Dev server: php -c php.dev.ini -S localhost:8000 -t public public/index.php
// (the router script makes the built-in server pass unknown paths here but still serve real files)
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\TemplateRenderer;

$config = new Config(require dirname(__DIR__) . '/config/config.php');

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

$request = Request::fromGlobals($config->get('locales'));

// Public pages need a language prefix; admin does not.
$isAdmin = str_starts_with($request->path, '/admin');
if ($request->locale === null && !$isAdmin) {
    $path = $request->path === '/' ? '/' : $request->path;
    Response::redirect('/' . $config->get('defaultLocale') . $path)->send();
    exit;
}

$container = new Container();
$container->set($container);
$container->set($config);
$container->set($request);

try {
    $router = new Router(
        $container,
        $request,
        $container->get(App\Core\Csrf::class),
        $container->get(App\Service\AuthService::class),
        require dirname(__DIR__) . '/config/routes.php',
    );
    $response = $router->dispatch()
        ?? $container->get(TemplateRenderer::class)->render($isAdmin ? 'admin/not-found.latte' : 'front/not-found.latte', [
            'titles' => $isAdmin ? $container->get(App\Service\CrudService::class)->titles() : [],
        ], 404);
    $response->send();
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    echo 'Server error.';
}
