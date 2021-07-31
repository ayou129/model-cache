<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache;

use Illuminate\Database\Eloquent\Builder as ModelBuilder;

class Builder extends ModelBuilder
{
    public function delete()
    {
        return $this->deleteCache(function () {
            return parent::delete();
        });
    }

    public function update(array $values)
    {
        return $this->deleteCache(function () use ($values) {
            return parent::update($values);
        });
    }

    protected function deleteCache(\Closure $closure)
    {
        $queryBuilder = clone $this;
        $primaryKey = $this->model->getKeyName();
        $ids = [];
        $models = $queryBuilder->get([$primaryKey]);
        foreach ($models as $model) {
            $ids[] = $model->{$primaryKey};
        }
        if (empty($ids)) {
            return 0;
        }

        $result = $closure();

        $manger = ApplicationContext::getContainer()->get(Manager::class);

        $manger->destroy($ids, get_class($this->model));

        return $result;
    }
}
