<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Repository\ThesisRepository;
use App\Repository\UserRepository;
use App\Service\LocaleService;

final class ThesisController extends BaseFrontController
{
    private const Categories = ['bachelor', 'master', 'both'];

    public function __construct(
        TemplateRenderer $view,
        private readonly ThesisRepository $theses,
        private readonly UserRepository $users,
        private readonly LocaleService $locale,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $messages = $this->locale->messages();
        $supervisors = [];
        foreach ($this->users->all() as $user) {
            $supervisors[$user['_id']] = trim($user['name'] . ' ' . $user['surname']);
        }
        $groups = [];
        foreach (self::Categories as $category) {
            $rows = $this->theses->findBy(['category', '=', $category], ['name' => 'asc']);
            // Rows saved before the flag existed have no "visible" key and count as visible.
            $rows = array_filter($rows, fn (array $row): bool => $row['visible'] ?? true);
            $items = $this->locale->localizeAll(array_values($rows), ['name', 'desc']);
            foreach ($items as &$item) {
                $item['supervisorName'] = $supervisors[$item['supervisor'] ?? null] ?? '';
            }
            unset($item);
            $groups[] = ['title' => $messages[$category], 'items' => $items];
        }
        return $this->view->render('front/theses.latte', ['groups' => $groups]);
    }
}
