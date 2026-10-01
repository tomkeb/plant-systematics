<?php

declare(strict_types=1);

namespace App\Markdown;

use App\Core\Config;
use App\Repository\PhotoRepository;
use App\Service\LocaleService;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Renders a gallery as <plant-gallery> holding one link (medium size) with a thumbnail per visible photo.
 * Without JavaScript the links open the images; the plant-gallery web component adds the lightbox.
 */
final class PlantGalleryRenderer implements NodeRendererInterface
{
    public function __construct(
        private readonly PhotoRepository $photos,
        private readonly LocaleService $locale,
        private readonly Config $config,
    ) {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        \assert($node instanceof PlantGalleryNode);
        if ($node->ids === []) {
            return '';
        }

        $rows = $this->photos->findBy([['_id', 'IN', $node->ids], 'AND', ['visible', '=', true]]);
        $byId = array_column($rows, null, '_id');
        $base = $this->config->get('uploadsUrl');

        $links = '';
        foreach ($node->ids as $id) {
            if (!isset($byId[$id])) {
                continue; // unknown or hidden photo
            }
            $photo = $this->locale->localize($byId[$id], ['name']);
            $name = htmlspecialchars((string) $photo['name'], ENT_QUOTES);
            $file = rawurlencode($photo['filename']);
            $links .= "<a href=\"{$base}/medium/{$file}\" title=\"{$name}\">"
                . "<img src=\"{$base}/thumb/{$file}\" alt=\"{$name}\" loading=\"lazy\"></a>";
        }

        return $links === '' ? '' : "<plant-gallery>{$links}</plant-gallery>\n";
    }
}
