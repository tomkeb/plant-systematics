<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Per-session CSRF token. Every POST form carries it in the hidden "_csrf" field.
 */
final class Csrf
{
    private const SessionKey = '_csrf';
    public const FieldName = '_csrf';

    public function token(): string
    {
        if (empty($_SESSION[self::SessionKey])) {
            $_SESSION[self::SessionKey] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SessionKey];
    }

    public function verify(string $submitted): bool
    {
        return $submitted !== '' && hash_equals($this->token(), $submitted);
    }
}
