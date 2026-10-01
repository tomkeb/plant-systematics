<?php

declare(strict_types=1);

namespace App\Core;

/**
 * One-shot messages shown on the next rendered page.
 */
final class Flash
{
    private const SessionKey = '_flash';

    public function add(string $message): void
    {
        $_SESSION[self::SessionKey][] = $message;
    }

    /**
     * @return list<string>
     */
    public function pull(): array
    {
        $messages = $_SESSION[self::SessionKey] ?? [];
        unset($_SESSION[self::SessionKey]);
        return $messages;
    }
}
