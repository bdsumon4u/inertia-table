<?php

namespace Hotash\InertiaTable;

use Illuminate\Contracts\Support\Arrayable;
use JetBrains\PhpStorm\ArrayShape;

class Column implements Arrayable
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $canBeHidden,
        public bool $hidden,
        public bool $sortable,
        public bool|string $sorted
    ) {
    }

    #[ArrayShape(['key' => 'string', 'label' => 'string', 'can_be_hidden' => 'bool', 'hidden' => 'bool', 'sortable' => 'bool', 'sorted' => 'bool|string'])]
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'can_be_hidden' => $this->canBeHidden,
            'hidden' => $this->hidden,
            'sortable' => $this->sortable,
            'sorted' => $this->sorted,
        ];
    }
}
