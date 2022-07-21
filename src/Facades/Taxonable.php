<?php

namespace Hotash\Taxonable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Hotash\Taxonable\TableBuilder
 */
class Taxonable extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-taxonable';
    }
}
