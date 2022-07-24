<?php

namespace Hotash\InertiaTable\Utils;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class SelectFilter implements Arrayable
{
    public function __construct(
        public string $key,
        public string $label,
        public array $options,
        public bool|string $placeholder,
        public ?string $value = null,
    ) {
        Arr::prepend($this->options, $this->placeholder, '-');
    }

    public function toArray(): array
    {
        return [
            'key'     => $this->key,
            'label'   => $this->label,
            'options' => $this->options,
            'value'   => $this->value,
        ];
    }
}
