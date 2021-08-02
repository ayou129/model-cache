<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache;

use Liguoxin129\ModelCache\Handler\HandlerInterface;
use Liguoxin129\ModelCache\Handler\RedisHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
// use Hyperf\DbConnection\Collector\TableCollector;

class Manager
{
    /**
     * @var HandlerInterface
     */
    protected $handler;

    protected $collector;

    public function __construct()
    {
        $config = config('database.connections.mysql');
        if (! $config) {
            throw new InvalidArgumentException('模型缓存配置不存在!');
        }
        $handleClass = $config['cache']['handler'] ?? RedisHandler::class;
        $config = new Config($config['cache'] ?? [], 'mysql');
        # FIXME 这里需要改成单例
        $this->handler = new $handleClass($config);
    }

    /**
     * @param $id
     * @param string $class
     * @return Model|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function findFromCache($id, string $class): ?Model
    {
        /** @var Model $instance */
        $instance = new $class();

        // $name = $instance->getConnectionName();
        $primaryKey = $instance->getKeyName();

        if ($this->handler) {
            $key = $this->getCacheKey($id, $instance, $this->handler->getConfig());
            $data = $this->handler->get($key);
            if ($data) {
                return $instance->newFromBuilder(
                    $this->getAttributes($this->handler->getConfig(), $instance, $data)
                );
            }

            // 从数据库中获取它，因为它不存在于缓存处理程序中。
            if (is_null($data)) {
                $model = $instance->newQuery()->where($primaryKey, '=', $id)->first();
                if ($model) {
                    $ttl = $this->getCacheTTL($instance, $this->handler);
                    $this->handler->set($key, $this->formatModel($model), $ttl);
                } else {
                    $ttl = $this->handler->getConfig()->getEmptyModelTtl();
                    $this->handler->set($key, [], $ttl);
                }
                return $model;
            }

            // 它不存在于缓存处理程序和数据库中。
            return null;
        }

        # 缓存处理程序不存在，请从数据库中提取数据。
        return $instance->newQuery()->where($primaryKey, '=', $id)->first();
    }

    /**
     * Fetch many models from cache.
     */
    public function findManyFromCache(array $ids, string $class): Collection
    {
        if (count($ids) === 0) {
            return new Collection([]);
        }

        /** @var Model $instance */
        $instance = new $class();

        // $name = $instance->getConnectionName();
        $primaryKey = $instance->getKeyName();

        if ($this->handler) {
            $keys = [];
            foreach ($ids as $id) {
                $keys[] = $this->getCacheKey($id, $instance, $this->handler->getConfig());
            }
            $data = $this->handler->getMultiple($keys);
            $items = [];
            $fetchIds = [];
            foreach ($data ?? [] as $item) {
                if (isset($item[$primaryKey])) {
                    $items[] = $item;
                    $fetchIds[] = $item[$primaryKey];
                }
            }

            // Get ids that not exist in cache handler.
            $targetIds = array_diff($ids, $fetchIds);
            if ($targetIds) {
                $models = $instance->newQuery()->whereIn($primaryKey, $targetIds)->get();
                $ttl = $this->getCacheTTL($instance, $this->handler);
                /** @var Model $model */
                foreach ($models as $model) {
                    $id = $model->getKey();
                    $key = $this->getCacheKey($id, $instance, $this->handler->getConfig());
                    $this->handler->set($key, $this->formatModel($model), $ttl);
                }

                $items = array_merge($items, $this->formatModels($models));
            }
            $map = [];
            foreach ($items as $item) {
                $map[$item[$primaryKey]] = $this->getAttributes($this->handler->getConfig(), $instance, $item);
            }

            $result = [];
            foreach ($ids as $id) {
                if (isset($map[$id])) {
                    $result[] = $map[$id];
                }
            }

            return $instance->hydrate($result);
        }

        # 缓存处理程序不存在，请从数据库中提取数据。
        // @phpstan-ignore-next-line
        return $instance->newQuery()->whereIn($primaryKey, $ids)->get();
    }

    /**
     * Destroy the models for the given IDs from cache.
     * @param mixed $ids
     */
    public function destroy($ids, string $class): bool
    {
        /** @var Model $instance */
        $instance = new $class();

        // $name = $instance->getConnectionName();
        if ($this->handler) {
            $keys = [];
            foreach ($ids as $id) {
                $keys[] = $this->getCacheKey($id, $instance, $this->handler->getConfig());
            }

            return $this->handler->deleteMultiple($keys);
        }

        return false;
    }

    /**
     * Increment a column's value by a given amount.
     * @param mixed $id
     * @param mixed $column
     * @param mixed $amount
     */
    public function increment($id, $column, $amount, string $class): bool
    {
        /** @var Model $instance */
        $instance = new $class();

        $name = $instance->getConnectionName();
        if ($this->handler) {
            $key = $this->getCacheKey($id, $instance, $this->handler->getConfig());
            if ($this->handler->has($key)) {
                return $this->handler->incr($key, $column, $amount);
            }

            return false;
        }

        # 缓存处理程序不存在，增量失败。
        return false;
    }

    /**
     * @return \DateInterval|int
     */
    protected function getCacheTTL(Model $instance, HandlerInterface $handler)
    {
        if ($instance instanceof CacheableInterface) {
            return $instance->getCacheTTL() ?? $handler->getConfig()->getTtl();
        }
        return $handler->getConfig()->getTtl();
    }

    /**
     * @param int|string $id
     */
    protected function getCacheKey($id, Model $model, Config $config): string
    {
        // mc:$prefix:m:$model:$pk:$id
        return sprintf(
            $config->getCacheKey(),
            $config->getPrefix(),
            $model->getTable(),
            $model->getKeyName(),
            $id
        );
    }

    protected function formatModel(Model $model): array
    {
        return $model->getAttributes();
    }

    protected function formatModels($models): array
    {
        $result = [];
        foreach ($models as $model) {
            $result[] = $this->formatModel($model);
        }

        return $result;
    }

    protected function getAttributes(Config $config, Model $model, array $data)
    {
        if (! $config->isUseDefaultValue()) {
            return $data;
        }

        $connection = $model->getConnectionName();
        $defaultData = $this->collector->getDefaultValue(
            $connection,
            $this->getPrefix($connection) . $model->getTable()
        );
        return array_replace($defaultData, $data);
    }

    protected function getPrefix(): string
    {
        return config('database.connections.mysql.prefix');
    }
}
