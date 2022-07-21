<?php

namespace Hotash\Taxonable\Traits;

trait HasTaxon
{
    /**
     * Get all of the taxons for the model.
     */
    public function taxons()
    {
        return $this->morphMany(config('taxonable.model'), 'taxonable');
    }
}
