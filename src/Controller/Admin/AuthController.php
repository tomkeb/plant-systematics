<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Service\AuthService;

final class AuthController
{
    public function __construct(
        private readonly TemplateRenderer $view,
        private readonly AuthService $auth,
        private readonly Flash $flash,
    ) {
    }

    public function loginForm(Request $request): Response
    {
        if ($this->auth->isAdmin()) {
            return Response::redirect('/admin');
        }
        return $this->view->render('admin/login.latte', ['errors' => []]);
    }

    public function login(Request $request): Response
    {
        if ($this->auth->login($request->post('email'), $request->post('password'))) {
            if ($this->auth->isAdmin()) {
                return Response::redirect('/admin');
            }
            $this->auth->logout();
            return $this->view->render('admin/login.latte', ['errors' => ['This account has no admin rights.']], 403);
        }

        $error = $this->auth->isThrottled()
            ? 'Too many failed attempts. Try again later.'
            : 'Wrong email or password.';
        return $this->view->render('admin/login.latte', ['errors' => [$error]], 401);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();
        $redirect = $request->post('redirect');
        // Only local paths: not "//host" or "/\host", which browsers read as another site.
        if (preg_match('#^/(?![/\\\\])#', $redirect) === 1) {
            return Response::redirect($redirect); // back to the public page the admin logged out from
        }
        $this->flash->add('Logged out.');
        return Response::redirect('/admin/login');
    }
}
