<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
use App\Markdown\PlantGalleryExtension;
use App\Markdown\PlantGalleryRenderer;
use App\Repository\PhotoRepository;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Renders admin-entered Markdown to HTML. Raw HTML in the source is dropped (except the
 * <plant-gallery ids="..."> tag, which is expanded to the listed photos) and unsafe link
 * schemes (javascript:, data:, ...) are blocked, so the result can be printed unescaped.
 */
final class MarkdownRenderer
{
    private readonly MarkdownConverter $converter;

    public function __construct(PhotoRepository $photos, LocaleService $locale, Config $config)
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new PlantGalleryExtension(new PlantGalleryRenderer($photos, $locale, $config)));
        $this->converter = new MarkdownConverter($environment);
    }

    public function render(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
