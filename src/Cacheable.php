<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Liguoxin129\ModelCache\Builder as ModelCacheBuilder;

trait Cacheable
{
    /**
     * @var bool
     */
    protected bool $useCacheBuilder = false;

    /**
     * Fetch a model from cache.
     * @param mixed $id
     */
    public static function findFromCache($id)
    {
        $manager = new Manager();

        return $manager->findFromCache($id, static::class);
    }

    /**
     * Fetch models from cache.
     */
    public static function findManyFromCache(array $ids): Collection
    {
        $manager = new Manager();

        $ids = array_unique($ids);
        return $manager->findManyFromCache($ids, static::class);
    }

    /**
     * 先有数据才能进行删除，所以非静态方法，目前只能一次删一个
     */
    public function deleteCache(): bool
    {
        $manager = new Manager();

        return $manager->destroy([$this->getKey()], get_called_class());
    }

    /**
     * Get the expire time for cache.
     */
    public function getCacheTTL(): ?int
    {
        return null;
    }

    /**
     * 按照指定列递增给定值
     * @param string $column
     * @param float|int $amount
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        # FIXME 这里和Mysql事务会有不一致的情况
        $res = parent::increment($column, $amount, $extra);
        if ($res > 0) {
            if (empty($extra)) {
                // 只增加值
                /** @var Manager $manager */
                $manager = new Manager();
                $manager->increment($this->getKey(), $column, $amount, get_called_class());
            } else {
                // 当增加值时，更新其他列
                $this->deleteCache();
            }
        }
        return $res;
    }

    /**
     * 按照指定列递减给定值
     * @param string $column
     * @param float|int $amount
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        # FIXME 这里和Mysql事务会有不一致的情况
        $res = parent::decrement($column, $amount, $extra);
        if ($res > 0) {
            if (empty($extra)) {
                // 只减少值
                $manager = new Manager();
                $manager->increment($this->getKey(), $column, -$amount, get_called_class());
            } else {
                // 当减少值时，更新其他列
                $this->deleteCache();
            }
        }
        return $res;
    }

    /**
     * 为模型创建新的模型查询生成器
     * @param QueryBuilder $query
     */
    public function newModelBuilder($query): Builder
    {
        if ($this->useCacheBuilder) {
            return new ModelCacheBuilder($query);
        }

        return parent::newModelBuilder($query);
    }

    public function newQuery(bool $cache = false): Builder
    {
        $this->useCacheBuilder = $cache;
        return parent::newQuery();
    }

    /**
     * @param bool $cache 批量更新时是否删除模型缓存
     */
    public static function query(bool $cache = false): Builder
    {
        return (new static())->newQuery($cache);
    }
}
