<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Cleans admin-entered HTML before it is stored, so templates can print it unescaped.
 */
final class HtmlSanitizer
{
    private ?HTMLPurifier $purifier = null;

    public function __construct(private readonly Config $config)
    {
    }

    public function clean(string $html): string
    {
        return $this->purifier()->purify($html);
    }

    private function purifier(): HTMLPurifier
    {
        if ($this->purifier === null) {
            $cache = $this->config->get('paths.var') . '/purifier';
            if (!is_dir($cache)) {
                mkdir($cache, 0775, true);
            }
            $purifierConfig = HTMLPurifier_Config::createDefault();
            $purifierConfig->set('Cache.SerializerPath', $cache);
            $purifierConfig->set('HTML.TargetBlank', true);
            $this->purifier = new HTMLPurifier($purifierConfig);
        }
        return $this->purifier;
    }
}
