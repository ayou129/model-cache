<?php

declare(strict_types=1);

namespace Lee\ModelCache;

use Hyperf\Utils\Traits\StaticInstance;

class InvalidCacheManager
{
    use StaticInstance;

    /**
     * @var CacheableInterface[]
     */
    protected $models = [];

    public function push(CacheableInterface $model): void
    {
        $this->models[] = $model;
    }

    public function delete(): void
    {
        while ($model = array_pop($this->models)) {
            $model->deleteCache();
        }
    }
}
