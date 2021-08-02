<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Handler;

use Psr\SimpleCache\CacheInterface;

interface HandlerInterface extends CacheInterface
{
    public function getConfig();

    public function incr($key, $column, $amount): bool;
}
