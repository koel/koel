<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TidyLibraryCommand extends Command
{
    protected $signature = 'koel:tidy';
    protected $hidden = true;

    public function handle(): int
    {
        $this->warn('koel:tidy has been renamed. Use koel:prune instead.');

        return self::SUCCESS;
    }
}
