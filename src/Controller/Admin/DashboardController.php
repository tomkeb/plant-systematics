<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Service\CrudService;

final class DashboardController
{
    public function __construct(
        private readonly TemplateRenderer $view,
        private readonly CrudService $crud,
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->view->render('admin/dashboard.latte', ['titles' => $this->crud->titles()]);
    }
}
