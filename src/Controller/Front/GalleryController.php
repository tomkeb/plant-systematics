<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Repository\PhotoCategoryRepository;
use App\Repository\PhotoRepository;
use App\Service\AdminLinks;
use App\Service\LocaleService;

final class GalleryController extends BaseFrontController
{
    public function __construct(
        TemplateRenderer $view,
        private readonly PhotoRepository $photos,
        private readonly PhotoCategoryRepository $categories,
        private readonly LocaleService $locale,
        private readonly AdminLinks $adminLinks,
    ) {
        parent::__construct($view);
    }

    /**
     * One section per category (a photo in several categories appears in each), newest photos first;
     * photos without a category come last. "?category=<id>" shows only that category.
     */
    public function index(Request $request): Response
    {
        $categoryId = $request->queryInt('category');
        $baseUrl = $this->locale->url('/gallery');

        $photos = $this->locale->localizeAll(
            $this->photos->findBy(['visible', '=', true], ['date' => 'desc', '_id' => 'desc']),
            ['name'],
        );

        $categories = [];
        $groups = [];
        foreach ($this->locale->localizeAll($this->categories->all(['name' => 'asc']), ['name']) as $category) {
            $categories[] = $category + [
                'url' => $baseUrl . '?category=' . $category['_id'],
                'active' => $category['_id'] === $categoryId,
            ];
            if ($categoryId === 0 || $category['_id'] === $categoryId) {
                $groups[] = [
                    'title' => $category['name'],
                    'photos' => array_values(array_filter($photos, fn (array $photo): bool => in_array($category['_id'], $photo['categoryIds'] ?? [], true))),
                ];
            }
        }
        if ($categoryId === 0) {
            $groups[] = [
                'title' => $this->locale->messages()['otherPhotos'],
                'photos' => array_values(array_filter($photos, fn (array $photo): bool => ($photo['categoryIds'] ?? []) === [])),
            ];
        }

        return $this->view->render('front/gallery.latte', [
            'groups' => array_values(array_filter($groups, fn (array $group): bool => $group['photos'] !== [])),
            'categories' => $categories,
            'allUrl' => $baseUrl,
            'allActive' => $categoryId === 0,
            'editUrl' => $this->adminLinks->list('photos'),
            'editLabel' => 'Manage photos',
        ]);
    }
}
