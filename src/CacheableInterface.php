<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface CacheableInterface
{
    public static function findFromCache($id): ?Model;

    public static function findManyFromCache(array $ids): Collection;

    public function deleteCache(): bool;

    public function getCacheTTL(): ?int;
}
