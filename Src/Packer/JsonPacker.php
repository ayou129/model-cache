<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Packer;

class JsonPacker implements PackerInterface
{
    public function pack($data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function unpack(string $data)
    {
        return json_decode($data, true);
    }
}
