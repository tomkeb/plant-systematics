<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Repository\ThesisRepository;
use App\Service\LocaleService;

final class ThesisController extends BaseFrontController
{
    private const Categories = ['bc', 'mgr', 'both'];

    public function __construct(
        TemplateRenderer $view,
        private readonly ThesisRepository $theses,
        private readonly LocaleService $locale,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $messages = $this->locale->messages();
        $groups = [];
        foreach (self::Categories as $category) {
            $rows = $this->theses->findBy(['category', '=', $category], ['name' => 'asc']);
            $groups[] = [
                'title' => $messages[$category],
                'items' => $this->locale->localizeAll($rows, ['name', 'desc']),
            ];
        }
        return $this->view->render('front/theses.latte', ['groups' => $groups]);
    }
}
