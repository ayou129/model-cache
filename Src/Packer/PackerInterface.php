<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Packer;

interface PackerInterface
{
    public function pack($data): string;

    public function unpack(string $data);
}
