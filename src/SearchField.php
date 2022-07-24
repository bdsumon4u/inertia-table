<?php

namespace Hotash\InertiaTable;

use Illuminate\Contracts\Support\Arrayable;

class SearchField implements Arrayable
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $value = null,
    ) {
    }

    public function toArray()
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'value' => $this->value,
        ];
    }
}
