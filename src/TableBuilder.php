<?php

namespace Hotash\InertiaTable;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Response;
use Laravel\Scout\Searchable;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

abstract class TableBuilder
{
    protected string $model;

    private Request $request;
    private string $pageName = 'page';
    private string $defaultSort = 'id';
    private Collection $fields;
    private Collection $columns;

    private Collection $searchFields;

    private Collection $filters;
    protected array $allowedSorts = [];
    protected array $allowedFilters = [];

    protected array $pageLength = [10, 20, 30, 50];

    private static bool|string $defaultGlobalSearch = false;

    private static array $defaultQueryBuilderConfig = [];
    private static array $customQueryBuilderConfig = [];

    public function __construct(private string $name = 'default')
    {
        $this->request = request();
        $this->fields = collect();
        $this->columns = collect();
        $this->searchFields = collect();
        $this->filters = collect();

        if ($this->name !== 'default') {
            $this->name($this->name);
        }

        if (static::$defaultGlobalSearch !== false) {
            $this->withGlobalSearch(static::$defaultGlobalSearch);
        }
    }

    public static function make(string $name = 'default'): static
    {
        return new static(...func_get_args());
    }

    abstract protected function buildTable(): void;

    abstract protected function buildQuery(QueryBuilder $builder): QueryBuilder;

    /**
     * Set a default for global search.
     *
     * @param bool|string $label
     * @return void
     */
    public static function defaultGlobalSearch(bool|string $label = 'Search...'): void
    {
        static::$defaultGlobalSearch = $label !== false ? __($label) : false;
    }

    /**
     * Retrieve a query string item from the request.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    private function query(string $key, mixed $default = null): mixed
    {
        if ($this->name !== 'default') {
            $key = "{$this->name}_{$key}";
        }

        return $this->request->query($key, $default);
    }

    /**
     * Helper method to update the Spatie Query Builder parameter config.
     *
     * @param string $name
     * @return void
     */
    public static function updateQueryBuilderParameters(string $name): void
    {
        if (empty(static::$defaultQueryBuilderConfig)) {
            static::$defaultQueryBuilderConfig = config('query-builder.parameters');
        }

        $newConfig = collect(static::$defaultQueryBuilderConfig)->map(function ($value) use ($name) {
            return "{$name}_{$value}";
        })->all();

        config(['query-builder.parameters' => $newConfig]);
    }

    /**
     * Name for this table.
     *
     * @param string $name
     * @return self
     */
    public function name(string $name): self
    {
        $this->name = $name;
        $this->pageName($name.'_page');

        return $this;
    }

    /**
     * PageName for this table.
     *
     * @param string $pageName
     * @return self
     */
    public function pageName(string $pageName): self
    {
        $this->pageName = $pageName;

        return $this;
    }

    /**
     * PerPage options for this table.
     *
     * @param array $pageLength
     * @return self
     */
    public function pageLength(array $pageLength): self
    {
        $this->pageLength = $pageLength;

        return $this;
    }

    /**
     * Default sort for this table.
     *
     * @param string $defaultSort
     * @return self
     */
    public function defaultSort(string $defaultSort): self
    {
        $this->defaultSort = $defaultSort;

        return $this;
    }

    /**
     * @return mixed
     */
    private function perPage(): mixed
    {
        return $this->query('per_page', Arr::first($this->pageLength));
    }

    /**
     * Disable the global search.
     *
     * @return self
     */
    public function disableGlobalSearch(): self
    {
        $this->globalSearch = false;

        return $this;
    }

    /**
     * Collects all properties and sets the default
     * values from the request query.
     *
     * @return array
     */
    protected function getQueryBuilderProps(): array
    {
        return [
            'defaultVisibleToggleableColumns' => $this->columns->reject->hidden->pluck('key')->sort()->values(),
            'columns' => $this->transformColumns(),

            'filters' => $this->transformFilters(),
            'hasFilters' => $this->filters->isNotEmpty(),
            'isFiltered' => !!$this->filters->search(fn($filter) => $filter->value),

            'searchFields' => $this->transformsearchFields(),

            'sort' => $this->query('sort', $this->defaultSort) ?: null,
            'defaultSort' => $this->defaultSort,
            'page' => Paginator::resolveCurrentPage($this->pageName),
            'pageName' => $this->pageName,
            'per_page' => $this->perPage(),
            'pageLength' => $this->pageLength,
        ];
    }

    /**
     * Transform the columns collection so it can be used in the Inertia front-end.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function transformColumns(): Collection
    {
        $columns = $this->query('columns', []);

        $sort = $this->query('sort', $this->defaultSort);

        return $this->columns->map(function (Column $column) use ($columns, $sort) {
            $key = $column->key;

            if (!empty($columns)) {
                $column->hidden = !in_array($key, $columns);
            }

            if ($sort === $key) {
                $column->sorted = 'asc';
            } elseif ($sort === "-{$key}") {
                $column->sorted = 'desc';
            }

            return $column;
        });
    }

    /**
     * Transform the search collection so it can be used in the Inertia front-end.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function transformFilters(): Collection
    {
        $filters = $this->query('filter', []);

        if (empty($filters)) {
            return $this->filters;
        }

        return $this->filters->map(function (Filter $filter) use ($filters) {
            if (array_key_exists($filter->key, $filters)) {
                $filter->value = $filters[$filter->key];
            }

            return $filter;
        });
    }

    /**
     * Transform the filters collection so it can be used in the Inertia front-end.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function transformSearchFields(): Collection
    {
        $filters = $this->query('filter', []);

        if (empty($filters)) {
            return $this->searchFields->except($this->filters->pluck('key'));
        }

        return $this->searchFields->map(function (SearchField $searchField) use ($filters) {
            if (array_key_exists($searchField->key, $filters)) {
                $searchField->value = $filters[$searchField->key];
            }

            return $searchField;
        })->except($this->filters->pluck('key'));
    }

    /**
     * Add a column to the query builder.
     *
     * @param string|null $key
     * @param string|null $label
     * @param bool $canBeHidden
     * @param bool $hidden
     * @param bool $sortable
     * @param bool $searchable
     * @return self
     */
    public function column(string $key = null, string $label = null, bool $canBeHidden = true, bool $hidden = false, bool $sortable = false, bool $searchable = false): self
    {
        $key = $key ?: Str::kebab($label);
        $label = $label ?: Str::headline($key);

        $this->columns->push(new Column(
            key: $key,
            label: $label,
            canBeHidden: $canBeHidden,
            hidden: $hidden,
            sortable: $sortable,
            sorted: false
        ));

        if ($searchable) {
            $this->searchField($key, $label);
        }

        return $this;
    }

    /**
     * Helper method to add a global search input.
     *
     * @param string|null $label
     * @return self
     */
    public function withGlobalSearch(string $label = null): self
    {
        return $this->searchField('global', $label ?: __('Search...'));
    }

    /**
     * Add a search input to query builder.
     *
     * @param string $key
     * @param string|null $label
     * @param string|null $defaultValue
     * @return self
     */
    public function searchField(string $key, string $label = null, string $defaultValue = null): self
    {
        $this->searchFields[$key] = new SearchField($key, $label ?: Str::headline($key), $defaultValue);

        return $this;
    }

    /**
     * Add a select filter to the query builder.
     *
     * @param string $key
     * @param array $options
     * @param string|null $label
     * @param string|null $defaultValue
     * @param bool $noFilterOption
     * @param string|null $noFilterOptionLabel
     * @return self
     */
    public function selectFilter(string $key, array $options, string $label = null, string $defaultValue = null, bool $noFilterOption = true, string $noFilterOptionLabel = null): self
    {
        $this->filters[$key] = new Filter(
            key: $key,
            type: 'select',
            label: $label ?: Str::headline($key),
            options: $options,
            noFilterOption: $noFilterOption,
            noFilterOptionLabel: $noFilterOptionLabel ?: '-',
            value: $defaultValue
        );

        return $this;
    }

    /**
     * Add multiple columns to the query builder.
     *
     * @param array $columns
     * @return $this
     */
    public function columns(array $columns = []): self
    {
        foreach ($columns as $key => $value) {
            $this->column($key, ...$value);
        }

        return $this;
    }

    public function getTableProps(): array
    {
        $this->buildTable();
        $this->allowedSorts();
        $this->allowedFilters();

        return array_merge($this->getQueryBuilderProps(), [
            'resource' => $this->buildQuery(QueryBuilder::for($this->model))
                ->defaultSort($this->defaultSort)
                ->allowedSorts($this->allowedSorts)
                ->allowedFilters($this->allowedFilters)
                ->allowedFields($this->allowedFields())
                ->paginate(
                    perPage: $this->perPage(),
                    pageName: $this->pageName,
                )
                ->through(function ($model, $key) {
                    $data = Arr::dot($model->toArray());
                    $data['created_at'] = $model->created_at->format('d-M-Y');
                    return $data;
                })
                ->withQueryString(),
        ]);
    }

    protected function allowedSorts(): void
    {
        if ($this->allowedSorts) {
            return;
        }

        $this->allowedSorts = $this->columns->filter->sortable->pluck('key')->toArray();
    }

    protected function allowedFilters(): void
    {
        if ($this->allowedFilters) {
            return;
        }

        $this->allowedFilters = $this->filters->merge($this->searchFields)->map(function ($column, $key) {
            if ($key !== 'global') {
                return $key;
            }
            return $this->globalSearch();
        })->values()->toArray();
    }

    public function globalSearch(): AllowedFilter
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

    private function allowedFields()
    {
        return [];
    }

    private function getQueryBuilderParameters()
    {
        if ($this->name === 'default') {
            return static::$defaultQueryBuilderConfig;
        }

        if (! isset(static::$customQueryBuilderConfig[$this->name])) {
            static::$customQueryBuilderConfig[$this->name] = collect(static::$defaultQueryBuilderConfig)->map(function ($value) {
                return "{$this->name}_{$value}";
            })->all();
        }

        return static::$customQueryBuilderConfig[$this->name];
    }

    private function resetQueryBuilderParameters(): void
    {
        if (empty(static::$defaultQueryBuilderConfig)) {
            static::$defaultQueryBuilderConfig = config('query-builder.parameters');
        }

        config(['query-builder.parameters' => $this->getQueryBuilderParameters()]);
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

        return $response->with([$this->name.'TableBuilderProps' => $this->getTableProps()]);
    }
}
