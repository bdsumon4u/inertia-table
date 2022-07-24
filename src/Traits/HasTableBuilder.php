<?php

namespace Hotash\InertiaTable\Traits;

use Hotash\InertiaTable\Utils\Column;
use Hotash\InertiaTable\Utils\SearchFilter;
use Hotash\InertiaTable\Utils\SelectFilter;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\QueryBuilder;

trait HasTableBuilder
{
    use HasQueryBuilder;

    protected array $perPageOptions = [10, 20, 30, 50];

    /**
     * @return mixed
     */
    private function perPage(): mixed
    {
        return $this->query('perPage', Arr::first($this->perPageOptions));
    }

    /**
     * Transform the column's collection, so it can be used in the Inertia front-end.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function transformColumns(): Collection
    {
        $columns = $this->query('fields', []);

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
    protected function transformSelectFilters(): Collection
    {
        $filters = $this->query('filter', []);

        if (empty($filters)) {
            return $this->selectFilters;
        }

        return $this->selectFilters->map(function (SelectFilter $filter) use ($filters) {
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
    protected function transformSearchFilters(): Collection
    {
        $filters = $this->query('filter', []);

        if (empty($filters)) {
            return $this->searchFilters;
        }

        return $this->searchFilters->map(function (SearchFilter $filter) use ($filters) {
            if (array_key_exists($filter->key, $filters)) {
                $filter->value = $filters[$filter->key];
            }

            return $filter;
        })
            // SelectFilter should not be in SearchFilter.
            ->except($this->transformSelectFilters()->pluck('key'));
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
            'defaultVisibleColumns' => $this->columns->reject->hidden->pluck('key')->sort()->values(),
            'columns' => $this->transformColumns(),

            'selectFilters' => $this->transformSearchFilters(),
            'searchFilters' => $this->transformSearchFilters(),

            'defaultSort' => $this->defaultSort,
            'sort' => $this->query('sort', $this->defaultSort) ?: null,
            'page' => Paginator::resolveCurrentPage($this->named('page')),
            'perPageOptions' => $this->perPageOptions,
            'perPage' => $this->perPage(),
        ];
    }

    private function queryBuilder(): QueryBuilder
    {
        $query = QueryBuilder::for($this->model);

        if (! $this->defaultSort) {
            return $query;
        }

        return $query->defaultSort($this->defaultSort);
    }

    public function getTableProps(): array
    {
        return array_merge($this->getQueryBuilderProps(), [
            'resource' => $this->queryBuilder()
                ->allowedSorts($this->allowedSorts())
                ->allowedFilters($this->allowedFilters())
                ->allowedFields($this->allowedFields())
                ->allowedIncludes(['company'])
                ->paginate(
                    perPage: $this->perPage(),
                    pageName: $this->named('page'),
                )
//                ->through(function ($model) {
//                    $data = Arr::dot($model->toArray());
////                    if (isset($data['created_at'])) {
////                        $data['created_at'] = $model->created_at->format('d-M-Y');
////                    }
//                    return $data;
//                })
                ->withQueryString(),
        ]);
    }

}
