<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache;

// use Hyperf\ModelCache\Listener\DeleteCacheInTransactionListener;
// use Hyperf\ModelCache\Listener\DeleteCacheListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                DeleteCacheListener::class,
                DeleteCacheInTransactionListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for database with model-cache.',
                    'source' => __DIR__ . '/../publish/databases.php',
                    'destination' => BASE_PATH . '/config/autoload/databases.php',
                ],
            ],
        ];
    }
}
