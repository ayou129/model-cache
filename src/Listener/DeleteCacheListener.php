<?php

declare(strict_types=1);

namespace Lee\ModelCache\Listener;

use Hyperf\Database\Model\Events\Deleted;
use Hyperf\Database\Model\Events\Event;
use Hyperf\Database\Model\Events\Saved;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\ModelCache\CacheableInterface;
use Hyperf\ModelCache\InvalidCacheManager;

class DeleteCacheListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            Deleted::class,
            Saved::class,
        ];
    }

    public function process(object $event)
    {
        if (! $event instanceof Event) {
            return;
        }

        $model = $event->getModel();
        if (! $model instanceof CacheableInterface) {
            return;
        }

        if ($model->getConnection()->transactionLevel() > 0) {
            InvalidCacheManager::instance()->push($model);
            return;
        }

        $model->deleteCache();
    }
}
