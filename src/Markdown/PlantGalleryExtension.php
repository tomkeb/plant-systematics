<?php

declare(strict_types=1);

namespace App\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;

/**
 * The only raw HTML Markdown may contain: <plant-gallery ids="1,2,3"></plant-gallery> on a line of its own.
 * Any other HTML is still stripped; the tag's only attribute is a list of photo ids.
 */
final class PlantGalleryExtension implements ExtensionInterface
{
    private const MaxPhotos = 100;
    private const OpenTag = '<plant-gallery\s+ids="([0-9,\s]*)"\s*>';
    private const CloseTag = '</plant-gallery>';

    public function __construct(private readonly PlantGalleryRenderer $renderer)
    {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this->replaceTags(...));
        $environment->addRenderer(PlantGalleryNode::class, $this->renderer);
    }

    private function replaceTags(DocumentParsedEvent $event): void
    {
        $replacements = [];
        foreach ($event->getDocument()->iterator() as $node) {
            $ids = match (true) {
                $node instanceof HtmlBlock => $this->idsFromBlock($node),
                $node instanceof Paragraph => $this->idsFromParagraph($node),
                default => null,
            };
            if ($ids !== null) {
                $replacements[] = [$node, new PlantGalleryNode($ids)];
            }
        }
        foreach ($replacements as [$old, $new]) {
            $old->replaceWith($new);
        }
    }

    /**
     * Tag(s) on their own lines, e.g. an open tag followed by a close tag on the next line.
     *
     * @return list<int>|null null when the block is anything else
     */
    private function idsFromBlock(HtmlBlock $block): ?array
    {
        $matched = preg_match('#^' . self::OpenTag . '\s*(?:' . self::CloseTag . ')?$#', trim($block->getLiteral()), $m);
        return $matched === 1 ? $this->parseIds($m[1]) : null;
    }

    /**
     * Open and close tag on the same line: CommonMark reads that as a paragraph of two inline HTML nodes.
     *
     * @return list<int>|null null when the paragraph is anything else
     */
    private function idsFromParagraph(Paragraph $paragraph): ?array
    {
        $tags = [];
        foreach ($paragraph->children() as $child) {
            if ($child instanceof HtmlInline) {
                $tags[] = trim($child->getLiteral());
            } elseif (!$child instanceof Newline && !($child instanceof Text && trim($child->getLiteral()) === '')) {
                return null;
            }
        }
        if (preg_match('#^' . self::OpenTag . '$#', $tags[0] ?? '', $m) !== 1) {
            return null;
        }
        $rest = array_slice($tags, 1);
        return $rest === [] || $rest === [self::CloseTag] ? $this->parseIds($m[1]) : null;
    }

    /**
     * @return list<int>
     */
    private function parseIds(string $list): array
    {
        $ids = array_map('intval', preg_split('/[\s,]+/', $list, -1, PREG_SPLIT_NO_EMPTY));
        return array_slice(array_values(array_unique(array_filter($ids, fn(int $id): bool => $id > 0))), 0, self::MaxPhotos);
    }
}
