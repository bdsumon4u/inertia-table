<?php

namespace Hotash\InertiaTable\Utils;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class Column implements Arrayable
{
    public bool $checkbox = false;

    public function __construct(
        public string $key,
        public string $label,
        public bool|AllowedSort|null $sortable = null,
        public bool|AllowedFilter|null $searchable = null,
        public bool $toggleable = true,
        public bool $hidden = false,
        public bool|string $sorted = false,
        public ?Closure $format = null,
    ) {
    }

    public static function make(string $key, string $label): static
    {
        return new static(...func_get_args());
    }

    public static function action(): static
    {
        return new static('action', null);
    }

    public static function checkbox(): static
    {
        return new static('checkbox', null);
    }

    public function sortable(AllowedSort|bool $sortable = true): static
    {
        $this->sortable = true;

        return $this;
    }

    public function searchable(AllowedFilter|bool $searchable = true): Column
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function toggleable(bool $toggleable = true): static
    {
        $this->toggleable = $toggleable;

        return $this;
    }

    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function sorted(bool|string $sorted = true): static
    {
        $this->sorted = $sorted;

        return $this;
    }

    public function format(Closure $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function isSearchable(): bool
    {
        return (bool) $this->searchable;
    }

    public function searchUsing(): AllowedFilter|string
    {
        if ($this->searchable instanceof AllowedFilter) {
            return $this->searchable;
        }

        return $this->key;
    }

    public function isSortable(): bool
    {
        return (bool) $this->sortable;
    }

    public function sortUsing(): AllowedSort|string
    {
        if ($this->sortable instanceof AllowedFilter) {
            return $this->sortable;
        }

        return $this->key;
    }

    public function hasFormatCallback(): bool
    {
        return $this->format !== null;
    }

    public function getFormatCallback(): callable|null
    {
        return $this->formatCallback;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'can_be_hidden' => $this->toggleable,
            'sortable' => $this->isSortable(),
            'hidden' => $this->hidden,
            'sorted' => $this->sorted,
        ];
    }
}
