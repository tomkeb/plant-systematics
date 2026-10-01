<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Config;
use SleekDB\Store;

/**
 * Thin wrapper around one SleekDB store (a "table" stored as one JSON file per row).
 */
abstract class BaseRepository
{
    protected const StoreName = '';

    protected readonly Store $store;

    public function __construct(Config $config)
    {
        $this->store = new Store(static::StoreName, $config->get('paths.data'), [
            'timeout' => false,
            'auto_cache' => false,
        ]);
    }

    /**
     * @param array<string, string>|null $orderBy e.g. ['date' => 'desc']
     * @return list<array<string, mixed>>
     */
    public function all(?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->store->findAll($orderBy, $limit, $offset);
    }

    /**
     * @param list<mixed> $criteria SleekDB criteria
     * @param array<string, string>|null $orderBy
     * @return list<array<string, mixed>>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->store->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->store->findById($id);
    }

    /**
     * @param list<mixed> $criteria
     * @return array<string, mixed>|null
     */
    public function findOneBy(array $criteria): ?array
    {
        return $this->store->findOneBy($criteria);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed> the stored row including "_id"
     */
    public function insert(array $data): array
    {
        return $this->store->insert($data);
    }

    /**
     * @param array<string, mixed> $data fields to merge into the row
     */
    public function update(int $id, array $data): void
    {
        $this->store->updateById($id, $data);
    }

    public function delete(int $id): void
    {
        $this->store->deleteById($id);
    }

    public function count(): int
    {
        return $this->store->count();
    }

    /**
     * @param list<mixed> $criteria
     */
    public function countBy(array $criteria): int
    {
        return count($this->store->findBy($criteria));
    }
}
