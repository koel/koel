<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\warning;

class TidyLibraryCommand extends Command
{
    protected $signature = 'koel:tidy';
    protected $hidden = true;

    public function handle(): int
    {
        warning('koel:tidy has been renamed. Use koel:prune instead.');

        return self::SUCCESS;
    }
}
