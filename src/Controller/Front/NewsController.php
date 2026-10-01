<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Repository\NewsRepository;
use App\Service\LocaleService;
use App\Service\PaginationService;

final class NewsController extends BaseFrontController
{
    public function __construct(
        TemplateRenderer $view,
        private readonly NewsRepository $news,
        private readonly LocaleService $locale,
        private readonly PaginationService $pagination,
        private readonly Config $config,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $perPage = $this->config->get('perPage.news');
        $pager = $this->pagination->build(
            $this->news->count(),
            $perPage,
            $request->queryInt('page', 1),
            $this->locale->url('/news'),
        );
        $items = $this->news->all(['date' => 'desc', '_id' => 'desc'], $perPage, $pager['offset']);

        return $this->view->render('front/news-list.latte', [
            'items' => $this->locale->localizeAll($items, ['name', 'text']),
            'pager' => $pager,
        ]);
    }

    public function detail(Request $request, int $id): Response
    {
        $item = $this->news->find($id);
        if ($item === null) {
            return $this->notFound();
        }
        return $this->view->render('front/news-detail.latte', [
            'item' => $this->locale->localize($item, ['name', 'text']),
            'listUrl' => $this->locale->url('/news'),
        ]);
    }
}
