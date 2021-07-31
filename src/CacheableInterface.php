<?php

declare(strict_types=1);

namespace LiGuoXin129\ModelCache;

use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Model;

interface CacheableInterface
{
    public static function findFromCache($id): ?Model;

    public static function findManyFromCache(array $ids): Collection;

    public function deleteCache(): bool;

    public function getCacheTTL(): ?int;
}
