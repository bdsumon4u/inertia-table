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

        Response::macro('getTableProps', function () {
            return $this->props['queryBuilderProps'] ?? [];
        });

        Response::macro('withTable', function (TableBuilder $withTableBuilder) {
            return $withTableBuilder->render($this);
        });
    }
}
