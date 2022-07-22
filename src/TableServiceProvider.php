<?php

namespace Hotash\InertiaTable;

use Hotash\InertiaTable\Commands\MakeTableCommand;
use Inertia\Response;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TableServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('inertia-table')
            ->hasConfigFile()
            ->hasCommand(MakeTableCommand::class);
    }

    public function boot()
    {
        parent::boot();

        Response::macro('withTable', function (mixed $withTableBuilder) {
            if (! is_subclass_of($withTableBuilder, TableBuilder::class)) {
                return $this;
            }

            if (! $withTableBuilder instanceof TableBuilder) {
                $withTableBuilder = new $withTableBuilder;
            }

            return $withTableBuilder->render($this);
        });
    }
}
