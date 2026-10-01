<?php

declare(strict_types=1);

namespace App\Core;

use App\Service\AdminLinks;
use App\Service\AuthService;
use App\Service\LocaleService;
use App\Service\MarkdownRenderer;
use App\Service\MenuService;
use Latte\Engine;
use Latte\Runtime\Html;

/**
 * Renders Latte templates with the globals every page needs (language, menu, CSRF token, flash messages),
 * a "markdown" filter that turns stored Markdown into (safe) HTML, and an "adminEditUrl" function.
 * Public pages can set "editUrl" (and "editLabel") to get an edit link in the admin bar shown to logged-in admins.
 */
final class TemplateRenderer
{
    private readonly Engine $latte;

    public function __construct(
        private readonly Config $config,
        private readonly Request $request,
        private readonly Csrf $csrf,
        private readonly Flash $flash,
        private readonly LocaleService $locale,
        private readonly MenuService $menu,
        private readonly AuthService $auth,
        MarkdownRenderer $markdown,
        private readonly AdminLinks $adminLinks,
    ) {
        $cache = $config->get('paths.var') . '/latte';
        if (!is_dir($cache)) {
            mkdir($cache, 0775, true);
        }
        $this->latte = new Engine();
        $this->latte->setTempDirectory($cache);
        $this->latte->addFunction('adminEditUrl', fn(string $entity, int $id): string => $this->adminLinks->edit($entity, $id));
        $this->latte->addFilter('markdown', fn(string $text): Html => new Html($markdown->render($text)));
    }

    /**
     * @param string $template path relative to the templates directory, e.g. "public/page.latte"
     * @param array<string, mixed> $params
     */
    public function render(string $template, array $params = [], int $status = 200): Response
    {
        $globals = [
            'lang' => $this->locale->current(),
            'otherLang' => $this->locale->other(),
            'otherLangUrl' => $this->locale->url($this->request->path, $this->locale->other()),
            'homeUrl' => $this->locale->url('/'),
            't' => $this->locale->messages(),
            'menu' => $this->menu->items(),
            'csrfField' => Csrf::FieldName,
            'csrfToken' => $this->csrf->token(),
            'flash' => $this->flash->pull(),
            'currentUser' => $this->auth->user(),
            'isAdmin' => $this->auth->isAdmin(),
            'currentUrl' => $this->locale->url($this->request->path),
            'editUrl' => null,
            'editLabel' => 'Edit this page',
            'uploadsUrl' => $this->config->get('uploadsUrl'),
        ];

        $html = $this->latte->renderToString(
            $this->config->get('paths.templates') . '/' . $template,
            $params + $globals,
        );
        return Response::html($html, $status);
    }
}
