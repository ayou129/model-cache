<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Handler;

use Liguoxin129\ModelCache\Packer\PackerInterface;
use Liguoxin129\ModelCache\Packer\PhpSerializerPacker;
use Liguoxin129\ModelCache\Config;
use Liguoxin129\ModelCache\Exception\CacheException;
use Liguoxin129\ModelCache\Redis\HashGetMultiple;
use Liguoxin129\ModelCache\Redis\HashIncr;
use Liguoxin129\ModelCache\Redis\LuaManager;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\InteractsWithTime;

class RedisStringHandler implements HandlerInterface
{
    use InteractsWithTime;

    protected $redis;

    /**
     * @var PackerInterface
     */
    protected $packer;

    public function __construct()
    {
        $this->redis = app('redis');
        $this->packer = new PhpSerializerPacker();
    }

    public function get($key, $default = null)
    {
        $data = $this->redis->get($key);
        if (! $data) {
            return $default;
        }

        return $this->packer->unpack($data);
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

        $serialized = $this->packer->pack($data);
        if ($ttl) {
            $seconds = $this->secondsUntil($ttl);
            if ($seconds > 0) {
                return $this->redis->set($key, $serialized, ['EX' => $seconds]);
            }
        }
        return $this->redis->set($key, $serialized);
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
        $data = $this->redis->mget($keys);
        $result = [];
        foreach ($data as $item) {
            if (! empty($item)) {
                $result[] = $this->packer->unpack($item);
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
        return $this->delete($key);
    }
}
