<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Handler;

use Hyperf\ModelCache\Config;
use Psr\SimpleCache\CacheInterface;

interface HandlerInterface extends CacheInterface
{
    public function getConfig(): Config;

    public function incr($key, $column, $amount): bool;
}
