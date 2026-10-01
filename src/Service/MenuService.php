<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
use App\Core\Request;

final class MenuService
{
    public function __construct(
        private readonly Config $config,
        private readonly Request $request,
        private readonly LocaleService $locale,
    ) {
    }

    /**
     * @return list<array{url: string, label: string, active: bool}>
     */
    public function items(): array
    {
        $items = [];
        foreach ($this->config->get('menu') as $entry) {
            $route = $entry['route'];
            $active = $route === '/'
                ? $this->request->path === '/'
                : str_starts_with($this->request->path, $route);
            $items[] = [
                'url' => $this->locale->url($route),
                'label' => $entry['label'][$this->locale->current()],
                'active' => $active,
            ];
        }
        return $items;
    }
}
