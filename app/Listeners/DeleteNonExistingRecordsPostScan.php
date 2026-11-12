<?php

namespace App\Listeners;

use App\Events\MediaScanCompleted;
use App\Repositories\SongRepository;
use App\Services\LibraryManager;
use App\Services\LibrarySummaryService;
use Illuminate\Support\Facades\Log;

readonly class DeleteNonExistingRecordsPostScan
{
    public function __construct(
        private SongRepository $songRepository,
        private LibraryManager $libraryManager,
        private LibrarySummaryService $librarySummaryService,
    ) {}

    public function handle(MediaScanCompleted $event): void
    {
        if ($this->wouldDeleteEverythingOnAnEmptyScan($event)) {
            Log::warning(
                'Scan reported no valid files while the library still holds songs. Refusing to '
                . 'delete them, because a media directory that has become empty, for example a '
                . 'bind mount whose backing filesystem went away, is indistinguishable from a '
                . 'library that was emptied on purpose. If it really was emptied, the rows '
                . 'will be removed by the next scan that finds any file.',
            );

            return;
        }

        $deleted = $this->libraryManager->deleteLocalSongsMissingFromScan($event->summary->scanId);

        if ($deleted > 0) {
            Log::info(sprintf('Removed %d song(s) no longer found in the media directory.', $deleted));
            $this->librarySummaryService->markLibraryChanged();
        }

        $this->libraryManager->prune();
    }

    /**
     * A scan that returns no valid files is ambiguous. The library may have been emptied on
     * purpose, or the media directory may still be there and no longer hold anything the scanner
     * can see: a bind mount whose backing filesystem went away leaves an empty but perfectly
     * readable directory, and audio sitting under subdirectories the scanner cannot enter is
     * skipped by ignoreUnreadableDirs() rather than reported. Both produce an empty result set,
     * and only the first makes deleting every remaining song correct.
     *
     * A media path that is missing, or unreadable at the root, is NOT one of these cases:
     * Finder::in() throws for the first and the iterator throws for the second, so no
     * MediaScanCompleted is ever dispatched and this listener never runs.
     */
    private function wouldDeleteEverythingOnAnEmptyScan(MediaScanCompleted $event): bool
    {
        if ($event->summary->hasValidFiles()) {
            return false;
        }

        return $this->songRepository->hasLocallyStoredSongs();
    }
}
