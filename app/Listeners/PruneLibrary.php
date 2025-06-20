<?php

namespace App\Listeners;

use App\Services\LibraryManager;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class PruneLibrary implements ShouldQueue
{
    public function __construct(private LibraryManager $libraryManager)
    {
    }

    public function handle(): void
    {
        $this->libraryManager->prune();
    }
}
