<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Redis;

interface OperatorInterface
{
    public function getScript(): string;

    public function parseResponse($data);
}
