<?php

namespace Hotash\InertiaTable\Traits;

use Hotash\InertiaTable\Utils\Column;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait HasQueryBuilder
{
    protected string $model;

    private Request $request;

    private string $defaultSort = '';

    protected static array $allowedSorts = [];

    protected static array $allowedFilters = [];

    private static array $customQueryBuilderConfig = [];

    private static array $defaultQueryBuilderConfig = [];

    public function withDefaultSort($sort): static
    {
        $this->defaultSort = $sort;

        return $this;
    }

    public function named(string $key): string
    {
        return $this->name === 'default' ? $key : "{$this->name}_{$key}";
    }

    /**
     * Retrieve a query string item from the request.
     *
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    private function query(string $key, mixed $default = null): mixed
    {
        return $this->request->query($this->named($key), $default);
    }

    private function allowedFields(): array
    {
        $table = (new $this->model)->getTable();
        $keys = $this->columns->pluck('key')->map(function ($column) use ($table) {
            return Str::contains($column, '.') ? $column : $table.'.'.$column;
        });

//        foreach ($this->query('fields', []) as $table => $columns) {
//            foreach (explode(',', $columns) as $column) {
//                $keys->push($table.'.'.$column);
//            }
//        }

        return $keys->unique()->values()->toArray();
    }

    private function allowedFilters()
    {
        if (! isset(static::$allowedFilters[static::class][$this->name])) {
            static::$allowedFilters[static::class][$this->name]
                = $this->columns->filter->sortable
                ->map(fn (Column $column) => $column->sortUsing())->toArray();
        }

        return static::$allowedFilters[static::class][$this->name];
    }

    private function allowedSorts()
    {
        if (! isset(static::$allowedSorts[static::class][$this->name])) {
            static::$allowedSorts[static::class][$this->name]
                = $this->columns->filter->searchable
                ->map(fn (Column $column) => $column->searchUsing())->toArray();
        }

        return static::$allowedSorts[static::class][$this->name];
    }

    private function getQueryBuilderParameters()
    {
        if ($this->name === 'default') {
            return static::$defaultQueryBuilderConfig;
        }

        if (! isset(static::$customQueryBuilderConfig[static::class][$this->name])) {
            static::$customQueryBuilderConfig[static::class][$this->name] = collect(static::$defaultQueryBuilderConfig)->map(function ($value) {
                return "{$this->name}_{$value}";
            })->all();
        }

        return static::$customQueryBuilderConfig[static::class][$this->name];
    }

    private function resetQueryBuilderParameters(): void
    {
        if (empty(static::$defaultQueryBuilderConfig)) {
            static::$defaultQueryBuilderConfig = config('query-builder.parameters');
        }

        config(['query-builder.parameters' => $this->getQueryBuilderParameters()]);
    }
}
