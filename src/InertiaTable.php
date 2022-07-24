<?php

namespace Hotash\InertiaTable;

use Hotash\InertiaTable\Utils\Column;
use Hotash\InertiaTable\Utils\SearchFilter;
use Hotash\InertiaTable\Utils\SelectFilter;
use Hotash\InertiaTable\Traits\HasTableBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Response;
use Laravel\Scout\Searchable;
use Spatie\QueryBuilder\AllowedFilter;

class InertiaTable
{
    use HasTableBuilder;

    private string $pageName = 'page';

    private Collection $columns;

    private Collection $searchFilters;

    private Collection $selectFilters;

    private static bool|string $defaultGlobalSearch = false;

    public function __construct(private string $name = 'default')
    {
        $this->request = \request();
        $this->columns = collect($this->columns());
        $this->searchFilters = collect();
        $this->selectFilters = collect();

        $this->columns->each(function (Column $column) {
            if (! $column->isSearchable()) {
                return;
            }

            $this->searchFilter($column->key, $column->label, null, $column->searchUsing());
        });

        if (static::$defaultGlobalSearch !== false) {
            $this->withGlobalSearch(static::$defaultGlobalSearch);
        }

        $this->buildTable();
    }

    protected function buildTable(): void
    {
    }

    public static function make(string $name = 'default'): static
    {
        return new static(...func_get_args());
    }

    /**
     * Set a default for global search.
     *
     * @param bool|string $label
     * @return void
     */
    public static function defaultGlobalSearch(bool|string $label = 'Search...'): void
    {
        static::$defaultGlobalSearch = is_string($label) ? __($label) : $label;
    }

    /**
     * Helper method to add a global search input.
     *
     * @param string $label
     * @return static
     */
    public function withGlobalSearch(string $label = 'Search...'): static
    {
        return $this->searchFilter('global', __($label), null, $this->globalSearch());
    }

    protected function globalSearch(): AllowedFilter
    {
        return AllowedFilter::callback('global', function ($query, $value) {
            if (! class_uses($this->model, Searchable::class)) {
                return $query;
            }

            $query->whereIn(
                (new $this->model)->getKeyName(),
                $this->model::search($value)->keys()
            );
        });
    }

    /**
     * Add a search input to query builder.
     *
     * @param string $key
     * @param string|null $label
     * @param string|null $value
     * @param AllowedFilter|null $filter
     * @return static
     */
    public function searchFilter(string $key, string $label = null, string $value = null, string|AllowedFilter $filter = null): static
    {
        $this->searchFilters->push(new SearchFilter($key, $label ?: Str::headline($key), $value, $filter ?: $key));

        return $this;
    }

    /**
     * Add a select filter to the query builder.
     *
     * @param string $key
     * @param array $options
     * @param string|null $label
     * @param bool|null $placeholder
     * @param string|null $value
     * @return static
     */
    public function selectFilter(string $key, array $options, string $label = null, bool $placeholder = null, string $value = null): static
    {
        $this->selectFilters->push(new SelectFilter(
            key: $key,
            label: $label ?: Str::headline($key),
            options: $options,
            placeholder: $placeholder ?: '-',
            value: $value
        ));

        return $this;
    }

    public function columns(): array
    {
        return [
            Column::make('id', __('ID'))->searchable()->sortable()->toggleable(),
            Column::make('name', __('Name'))->searchable()->sortable()->toggleable(),
            Column::make('company.id', __('Company ID'))->searchable()->sortable()->toggleable(),
            Column::make('company.name', __('Company Name'))->searchable()->sortable()->toggleable(),
            Column::make('email', __('Email'))->searchable()->sortable()->toggleable(),
            Column::make('created_at', __('Created AT'))->searchable()->sortable()->toggleable(),
        ];
    }

    /**
     * Give the query builder props to the given Inertia response.
     *
     * @param \Inertia\Response $response
     * @return \Inertia\Response
     */
    public function render(Response $response): Response
    {
        $this->resetQueryBuilderParameters();

        return $response->with([
            $this->name.'TableBuilderProps' => $this->getTableProps()
        ]);
    }
}
