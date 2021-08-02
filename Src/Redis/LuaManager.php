<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Redis;

use Liguoxin129\ModelCache\Config;
use Liguoxin129\ModelCache\Exception\OperatorNotFoundException;
use App\Services\Reuse;
use Illuminate\Redis\RedisManager;

class LuaManager
{
    /**
     * @var array<string,OperatorInterface>
     */
    protected $operators = [];

    /**
     * @var array<string,string>
     */
    protected $luaShas = [];

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var RedisManager
     */
    protected $redis;

    public function __construct(Config $config)
    {
        $this->redis = Reuse::getRedis();
        // dd($this->redis);
        $this->config = $config;
        $this->operators[HashGetMultiple::class] = new HashGetMultiple();
        $this->operators[HashIncr::class] = new HashIncr();
    }

    public function handle(string $key, array $keys, ?int $num = null)
    {
        if ($this->config->isLoadScript()) {
            $sha = $this->getLuaSha($key);
        }

        $operator = $this->getOperator($key);

        if ($num === null) {
            $num = count($keys);
        }

        if (! empty($sha)) {
            $luaData = $this->redis->evalSha($sha, $num, ...$keys);
        } else {
            $script = $operator->getScript();
            $luaData = $this->redis->eval($script, $num, ...$keys);
        }

        return $operator->parseResponse($luaData);
    }

    public function getOperator(string $key): OperatorInterface
    {
        if (! isset($this->operators[$key])) {
            throw new OperatorNotFoundException(sprintf('The operator %s is not found.', $key));
        }

        if (! $this->operators[$key] instanceof OperatorInterface) {
            throw new OperatorNotFoundException(sprintf('The operator %s is not instanceof OperatorInterface.', $key));
        }

        return $this->operators[$key];
    }

    public function getLuaSha(string $key): string
    {
        if (empty($this->luaShas[$key])) {
            $this->luaShas[$key] = $this->redis->script('load', $this->getOperator($key)->getScript());
        }
        return $this->luaShas[$key];
    }
}
