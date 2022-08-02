<?php

namespace App\Listeners;

use App\Services\LibraryManager;

class PruneLibrary
{
    public function __construct(private LibraryManager $libraryManager)
    {
    }

    public function handle(): void
    {
        $this->libraryManager->prune();
    }
}
