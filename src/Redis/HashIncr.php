<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Redis;

class HashIncr implements OperatorInterface
{
    public function getScript(): string
    {
        return <<<'LUA'
    if(redis.call('type', KEYS[1]).ok == 'hash') then
        return redis.call('HINCRBYFLOAT', KEYS[1], ARGV[1], ARGV[2]);
    end
    return false;
LUA;
    }

    public function parseResponse($data)
    {
        return $data;
    }
}
