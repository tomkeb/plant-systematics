<?php

declare(strict_types=1);

namespace App\Markdown;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * A <plant-gallery ids="1,2,3"> tag found in Markdown, as a block that renders the listed photos.
 */
final class PlantGalleryNode extends AbstractBlock
{
    /**
     * @param list<int> $ids photo _ids in the order they were written
     */
    public function __construct(public readonly array $ids)
    {
        parent::__construct();
    }
}
