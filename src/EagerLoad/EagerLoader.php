<?php

declare(strict_types=1);
namespace Liguoxin129\ModelCache\EagerLoad;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EagerLoader
{
    public function load(Collection $collection, array $relations)
    {
        if ($collection->isNotEmpty()) {
            /** @var Model $first */
            $first = $collection->first();
            $query = $first->registerGlobalScopes($this->newBuilder($first))
                ->with($relations);
            $collection->fill($query->eagerLoadRelations($collection->all()));
        }
    }

    protected function newBuilder(Model $model): Builder
    {
        $builder = new EagerLoaderBuilder($this->newBaseQueryBuilder($model));

        return $builder->setModel($model);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     */
    protected function newBaseQueryBuilder(Model $model): QueryBuilder
    {
        $connection = $model->getConnection();

        return new QueryBuilder($connection, $connection->getQueryGrammar(), $connection->getPostProcessor());
    }
}
