<?php

namespace Hotash\InertiaTable;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use JetBrains\PhpStorm\ArrayShape;

class Filter implements Arrayable
{
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public array $options,
        public bool $noFilterOption,
        public string $noFilterOptionLabel,
        public ?string $value = null,
    ) {
    }

    #[ArrayShape(['key' => "string", 'label' => "string", 'options' => "array", 'value' => "null|string", 'type' => "string"])]
    public function toArray(): array
    {
        $options = $this->options;

        if ($this->noFilterOption) {
            $options = Arr::prepend($options, $this->noFilterOptionLabel, '');
        }

        return [
            'key'     => $this->key,
            'label'   => $this->label,
            'options' => $options,
            'value'   => $this->value,
            'type'    => $this->type,
        ];
    }
}
