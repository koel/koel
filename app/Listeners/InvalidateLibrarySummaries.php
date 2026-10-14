<?php

namespace App\Listeners;

use App\Services\LibrarySummaryService;

readonly class InvalidateLibrarySummaries
{
    public function __construct(
        private LibrarySummaryService $librarySummaryService,
    ) {}

    public function handle(): void
    {
        $this->librarySummaryService->markLibraryChanged();
    }
}
