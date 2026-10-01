<?php

declare(strict_types=1);

namespace App\Service;

/**
 * URLs of the admin screens (the routes of CrudController), so public pages can link to them.
 */
final class AdminLinks
{
    public function list(string $entity): string
    {
        return "/admin/{$entity}";
    }

    public function edit(string $entity, int $id): string
    {
        return "/admin/{$entity}/{$id}/edit";
    }
}
