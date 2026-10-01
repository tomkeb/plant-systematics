<?php

declare(strict_types=1);

namespace App\Core;

use App\Service\AuthService;

/**
 * Matches the request path against config/routes.php and calls the controller action.
 * Also enforces the two cross-cutting admin rules: CSRF check on every POST and login for /admin/*.
 */
final class Router
{
    private const PublicAdminPaths = ['/admin/login'];

    /**
     * @param list<array{0: string, 1: string, 2: array{0: class-string, 1: string}}> $routes [method, pattern, [Controller, action]]
     */
    public function __construct(
        private readonly Container $container,
        private readonly Request $request,
        private readonly Csrf $csrf,
        private readonly AuthService $auth,
        private readonly array $routes,
    ) {
    }

    public function dispatch(): ?Response
    {
        if ($this->request->isPost() && !$this->csrf->verify($this->request->post(Csrf::FieldName))) {
            return Response::html('Invalid or missing CSRF token.', 403);
        }

        $isAdmin = str_starts_with($this->request->path, '/admin');
        if ($isAdmin && !in_array($this->request->path, self::PublicAdminPaths, true) && !$this->auth->isAdmin()) {
            return Response::redirect('/admin/login');
        }

        foreach ($this->routes as [$method, $pattern, [$class, $action]]) {
            if ($method !== $this->request->method) {
                continue;
            }
            if (preg_match($this->toRegex($pattern), $this->request->path, $matches) !== 1) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            if (isset($params['id'])) {
                $params['id'] = (int) $params['id'];
            }
            return $this->container->get($class)->$action($this->request, ...$params);
        }

        return null;
    }

    private function toRegex(string $pattern): string
    {
        $regex = preg_replace_callback('/\{(\w+)\}/', function (array $m): string {
            $expression = $m[1] === 'id' ? '\d+' : '[A-Za-z0-9_-]+';
            return "(?P<{$m[1]}>{$expression})";
        }, $pattern);
        return '#^' . $regex . '$#';
    }
}
