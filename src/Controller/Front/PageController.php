<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Repository\PageRepository;
use App\Service\LocaleService;

/**
 * Static pages stored in the "pages" table (home, people, research, courses and any /page/{slug}).
 */
final class PageController extends BaseFrontController
{
    public function __construct(
        TemplateRenderer $view,
        private readonly PageRepository $pages,
        private readonly LocaleService $locale,
    ) {
        parent::__construct($view);
    }

    public function home(Request $request): Response
    {
        return $this->show('home');
    }

    public function people(Request $request): Response
    {
        return $this->show('people');
    }

    public function research(Request $request): Response
    {
        return $this->show('research');
    }

    public function courses(Request $request): Response
    {
        return $this->show('courses');
    }

    public function page(Request $request, string $slug): Response
    {
        return $this->show($slug);
    }

    private function show(string $slug): Response
    {
        $page = $this->pages->findOneBy(['slug', '=', $slug]);
        if ($page === null) {
            return $this->notFound();
        }
        return $this->view->render('front/page.latte', [
            'page' => $this->locale->localize($page, ['name', 'text']),
        ]);
    }
}
