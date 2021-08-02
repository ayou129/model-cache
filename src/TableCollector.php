<?php

declare(strict_types=1);

namespace Hyperf\DbConnection\Collector;

use Hyperf\Database\Schema\Column;
use Illuminate\Database\Schema\ColumnDefinition;

class TableCollector
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param Column[] $columns
     */
    public function set(string $pool, string $table, array $columns)
    {
        $this->validateColumns($columns);
        $this->data[$pool][$table] = $columns;
    }

    public function add(string $pool, Column $column)
    {
        $this->data[$pool][$column->getTable()][$column->getName()] = $column;
    }

    public function get(string $pool, ?string $table = null): array
    {
        if ($table === null) {
            return $this->data[$pool] ?? [];
        }

        return $this->data[$pool][$table] ?? [];
    }

    public function has(string $pool, ?string $table = null): bool
    {
        return ! empty($this->get($pool, $table));
    }

    public function getDefaultValue(string $connectName, string $table): array
    {
        $columns = $this->get($connectName, $table);
        $list = [];
        foreach ($columns as $column) {
            $list[$column->getName()] = $column->getDefault();
        }
        return $list;
    }

    /**
     * @throws \InvalidArgumentException When $columns is not equal to Column[]
     */
    protected function validateColumns(array $columns): void
    {
        foreach ($columns as $column) {
            if (! $column instanceof Column) {
                throw new \InvalidArgumentException('Invalid columns.');
            }
        }
    }
}
