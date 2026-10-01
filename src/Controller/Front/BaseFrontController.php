<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Core\Response;
use App\Core\TemplateRenderer;

abstract class BaseFrontController
{
    public function __construct(protected readonly TemplateRenderer $view)
    {
    }

    protected function notFound(): Response
    {
        return $this->view->render('front/not-found.latte', [], 404);
    }
}
