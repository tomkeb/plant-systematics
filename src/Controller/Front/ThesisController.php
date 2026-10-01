<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Core\Request;
use App\Core\Response;
use App\Core\TemplateRenderer;
use App\Repository\ThesisRepository;
use App\Repository\UserRepository;
use App\Service\AdminLinks;
use App\Service\LocaleService;

final class ThesisController extends BaseFrontController
{
    public function __construct(
        TemplateRenderer $view,
        private readonly ThesisRepository $theses,
        private readonly UserRepository $users,
        private readonly LocaleService $locale,
        private readonly AdminLinks $adminLinks,
    ) {
        parent::__construct($view);
    }

    /**
     * Theses grouped by supervisor (groups ordered by surname, theses by name); theses without a
     * (still existing) supervisor come last.
     */
    public function index(Request $request): Response
    {
        $messages = $this->locale->messages();
        // Rows saved before the flag existed have no "visible" key and count as visible.
        $rows = array_filter($this->theses->all(['name' => 'asc']), fn (array $row): bool => $row['visible'] ?? true);

        $bySupervisor = [];
        foreach ($this->locale->localizeAll(array_values($rows), ['name', 'desc']) as $item) {
            $item['categoryName'] = $messages[$item['category']] ?? '';
            $bySupervisor[$item['supervisor'] ?? 0][] = $item;
        }

        $groups = [];
        foreach ($this->users->all(['surname' => 'asc', 'name' => 'asc']) as $user) {
            if (isset($bySupervisor[$user['_id']])) {
                $groups[] = ['title' => trim($user['name'] . ' ' . $user['surname']), 'items' => $bySupervisor[$user['_id']]];
                unset($bySupervisor[$user['_id']]);
            }
        }
        if ($bySupervisor !== []) {
            $groups[] = ['title' => $messages['noSupervisor'], 'items' => array_merge(...array_values($bySupervisor))];
        }

        return $this->view->render('front/theses.latte', [
            'groups' => $groups,
            'editUrl' => $this->adminLinks->list('theses'),
            'editLabel' => 'Manage theses',
        ]);
    }
}
