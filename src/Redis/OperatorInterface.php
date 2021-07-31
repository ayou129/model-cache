<?php

declare(strict_types=1);

namespace Lee\ModelCache\Redis;

interface OperatorInterface
{
    public function getScript(): string;

    public function parseResponse($data);
}
