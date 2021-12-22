<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TidyLibraryCommand extends Command
{
    protected $signature = 'koel:tidy';
    protected $hidden = true;

    public function handle(): void
    {
        $this->warn('koel:tidy has been renamed. Use koel:prune instead.');
    }
}
