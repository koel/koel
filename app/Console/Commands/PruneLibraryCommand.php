<?php

namespace App\Console\Commands;

use App\Events\LibraryChanged;
use Illuminate\Console\Command;

class PruneLibraryCommand extends Command
{
    protected $signature = 'koel:prune';
    protected $description = 'Remove empty artists and albums';

    public function handle(): void
    {
        event(new LibraryChanged());
        $this->info('Empty artists and albums removed.');
    }
}
