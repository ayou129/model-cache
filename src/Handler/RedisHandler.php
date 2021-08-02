<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Handler;

use Liguoxin129\ModelCache\Config;
use Liguoxin129\ModelCache\Exception\CacheException;
use Liguoxin129\ModelCache\Redis\HashGetMultiple;
use Liguoxin129\ModelCache\Redis\HashIncr;
use Liguoxin129\ModelCache\Redis\LuaManager;
use App\Services\Reuse;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\InteractsWithTime;

class RedisHandler implements HandlerInterface
{
    use InteractsWithTime;
    protected $redis;

    /**
     * @var LuaManager
     */
    protected LuaManager $manager;

    /**
     * @var Config
     */
    protected Config $config;

    protected string $defaultKey = 'HF-DATA';

    protected string $defaultValue = 'DEFAULT';

    public function __construct(Config $config)
    {
        $this->redis = Reuse::getRedis();
        $this->config = $config;
        $this->manager = new LuaManager($config);
    }

    public function get($key, $default = null)
    {
        $data = $this->redis->hGetAll($key);
        if (! $data) {
            return $default;
        }

        unset($data[$this->defaultKey]);

        if (empty($data)) {
            return [];
        }

        return $data;
    }

    public function set($key, $value, $ttl = null)
    {
        if (is_array($value)) {
            $data = $value;
        } elseif ($value instanceof Arrayable) {
            $data = $value->toArray();
        } else {
            throw new CacheException(sprintf('The value must is array.'));
        }

        $data = array_merge($data, [$this->defaultKey => $this->defaultValue]);
        $res = $this->redis->hMSet($key, $data);
        if ($ttl) {
            $seconds = $this->secondsUntil($ttl);
            if ($seconds > 0) {
                $this->redis->expire($key, $seconds);
            }
        }

        return $res;
    }

    public function delete($key)
    {
        return (bool) $this->redis->del($key);
    }

    public function clear()
    {
        throw new CacheException('Method clear is forbidden.');
    }

    public function getMultiple($keys, $default = null)
    {
        $data = $this->manager->handle(HashGetMultiple::class, $keys);
        $result = [];
        foreach ($data as $item) {
            unset($item[$this->defaultKey]);
            if (! empty($item)) {
                $result[] = $item;
            }
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new CacheException('Method setMultiple is forbidden.');
    }

    public function deleteMultiple($keys)
    {
        return $this->redis->del(...$keys) > 0;
    }

    public function has($key)
    {
        return (bool) $this->redis->exists($key);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function incr($key, $column, $amount): bool
    {
        $data = $this->manager->handle(HashIncr::class, [$key, $column, $amount], 1);

        return is_numeric($data);
    }
}
