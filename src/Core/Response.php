<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private function __construct(
        private readonly string $body,
        private readonly int $status,
        private readonly ?string $location = null,
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status);
    }

    public static function redirect(string $url): self
    {
        return new self('', 302, $url);
    }

    public function send(): void
    {
        http_response_code($this->status);
        if ($this->location !== null) {
            header('Location: ' . $this->location);
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo $this->body;
    }
}
