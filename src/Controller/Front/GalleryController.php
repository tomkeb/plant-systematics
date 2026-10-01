<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Repository\PhotoCategoryRepository;
use App\Repository\PhotoRepository;
use App\Service\LocaleService;
use App\Service\PaginationService;

final class GalleryController extends BaseFrontController
{
    public function __construct(
        TemplateRenderer $view,
        private readonly PhotoRepository $photos,
        private readonly PhotoCategoryRepository $categories,
        private readonly LocaleService $locale,
        private readonly PaginationService $pagination,
        private readonly Config $config,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $categoryId = $request->queryInt('category');
        $criteria = ['visible', '=', true];
        if ($categoryId > 0) {
            $criteria = [$criteria, 'AND', ['categoryId', '=', $categoryId]];
        }

        $perPage = $this->config->get('perPage.gallery');
        $baseUrl = $this->locale->url('/gallery');
        $extraQuery = $categoryId > 0 ? ['category' => $categoryId] : [];
        $pager = $this->pagination->build(
            $this->photos->countBy($criteria),
            $perPage,
            $request->queryInt('page', 1),
            $baseUrl,
            $extraQuery,
        );
        $photos = $this->photos->findBy($criteria, ['date' => 'desc', '_id' => 'desc'], $perPage, $pager['offset']);

        $categories = [];
        foreach ($this->locale->localizeAll($this->categories->all(['name' => 'asc']), ['name']) as $category) {
            $categories[] = $category + [
                'url' => $baseUrl . '?category=' . $category['_id'],
                'active' => $category['_id'] === $categoryId,
            ];
        }

        return $this->view->render('front/gallery.latte', [
            'photos' => $this->locale->localizeAll($photos, ['name', 'desc']),
            'categories' => $categories,
            'allUrl' => $baseUrl,
            'allActive' => $categoryId === 0,
            'pager' => $pager,
        ]);
    }
}
