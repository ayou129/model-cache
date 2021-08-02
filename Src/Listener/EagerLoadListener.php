<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Listener;

use Illuminate\Database\Eloquent\Collection;

// use Hyperf\Framework\Event\BootApplication;
// use BootApplication
use Liguoxin129\ModelCache\EagerLoad\EagerLoader;
use Psr\Container\ContainerInterface;

class EagerLoadListener implements ListenerInterface
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event)
    {
        $eagerLoader = $this->container->get(EagerLoader::class);
        Collection::macro('loadCache', function ($parameters) use ($eagerLoader) {
            $eagerLoader->load($this, $parameters);
        });
    }
}
