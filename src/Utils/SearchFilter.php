<?php

namespace Hotash\InertiaTable\Utils;

use Illuminate\Contracts\Support\Arrayable;
use Spatie\QueryBuilder\AllowedFilter;

class SearchFilter implements Arrayable
{
    public function __construct(
        public string                    $key,
        public string                    $label,
        public ?string                   $value = null,
        public string|AllowedFilter|null $filter = null,
    ) {
    }

    public function toArray()
    {
        return [
            'key'   => $this->key,
            'label' => $this->label,
            'value' => $this->value,
        ];
    }
}
