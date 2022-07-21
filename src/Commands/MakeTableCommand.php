<?php

namespace Hotash\InertiaTable\Commands;

use Illuminate\Console\Command;

class MakeTableCommand extends Command
{
    public $signature = 'make:table';

    public $description = 'This command creates inertia data-table class.';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
