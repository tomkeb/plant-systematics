<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
use App\Core\Request;

/**
 * Current language and picking the right language variant of bilingual rows.
 */
final class LocaleService
{
    public function __construct(private readonly Config $config, private readonly Request $request)
    {
    }

    public function current(): string
    {
        return $this->request->locale ?? $this->config->get('defaultLocale');
    }

    public function other(): string
    {
        foreach ($this->config->get('locales') as $locale) {
            if ($locale !== $this->current()) {
                return $locale;
            }
        }
        return $this->current();
    }

    /**
     * @return array<string, string> UI strings for the current language
     */
    public function messages(): array
    {
        return $this->config->get('messages.' . $this->current(), []);
    }

    public function url(string $route, ?string $locale = null): string
    {
        return '/' . ($locale ?? $this->current()) . ($route === '/' ? '/' : $route);
    }

    /**
     * Replaces each "<field>" with its "<field>En" variant when English is active and the variant is filled.
     *
     * @param array<string, mixed> $row
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    public function localize(array $row, array $fields): array
    {
        if ($this->current() !== 'en') {
            return $row;
        }
        foreach ($fields as $field) {
            $english = $row[$field . 'En'] ?? '';
            if ($english !== '') {
                $row[$field] = $english;
            }
        }
        return $row;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    public function localizeAll(array $rows, array $fields): array
    {
        return array_map(fn(array $row) => $this->localize($row, $fields), $rows);
    }
}
