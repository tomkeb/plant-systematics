<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable snapshot of the HTTP request. Built once in the entrypoint from the superglobals,
 * so nothing else in the application touches $_GET / $_POST / $_FILES.
 */
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @param string|null $locale language from the URL prefix, null when the URL has none
     * @param string $path path without the language prefix, always starting with "/"
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly ?string $locale,
        private readonly array $query,
        private readonly array $post,
        private readonly array $files,
        public readonly string $ip,
    ) {
    }

    /**
     * @param list<string> $locales supported language codes
     */
    public static function fromGlobals(array $locales): self
    {
        $path = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        $locale = null;
        if (preg_match('#^/(' . implode('|', $locales) . ')(/.*)?$#', $path, $m)) {
            $locale = $m[1];
            $path = $m[2] ?? '/';
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $locale,
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER['REMOTE_ADDR'] ?? '',
        );
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function query(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    public function queryInt(string $key, int $default = 0): int
    {
        return (int) ($this->query[$key] ?? $default);
    }

    public function post(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    public function postFlag(string $key): bool
    {
        return !empty($this->post[$key]);
    }

    /**
     * Uploaded files for a (possibly multiple) file input, normalised to a list of single-file arrays.
     *
     * @return list<array{name: string, tmp_name: string, error: int, size: int}>
     */
    public function files(string $key): array
    {
        $entry = $this->files[$key] ?? null;
        if (!is_array($entry)) {
            return [];
        }
        if (!is_array($entry['name'])) {
            return [$entry];
        }
        $result = [];
        foreach (array_keys($entry['name']) as $i) {
            $result[] = [
                'name' => $entry['name'][$i],
                'tmp_name' => $entry['tmp_name'][$i],
                'error' => $entry['error'][$i],
                'size' => $entry['size'][$i],
            ];
        }
        return $result;
    }
}
