<?php

namespace App\Listeners;

use App\Events\MediaScanCompleted;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\LibraryManager;
use App\Values\Scanning\ScanResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

readonly class DeleteNonExistingRecordsPostScan implements ShouldQueue
{
    public function __construct(
        private SongRepository $songRepository,
        private LibraryManager $libraryManager,
    ) {}

    public function handle(MediaScanCompleted $event): void
    {
        $paths = $event
            ->results
            ->valid()
            ->map(static fn (ScanResult $result) => $result->path)
            ->merge($this->songRepository->getAllStoredOnCloud()->pluck('path'))
            ->toArray();

        if ($this->wouldDeleteEverythingOnAnEmptyScan($event, $paths)) {
            Log::warning(
                'Scan reported no valid files while the library still holds songs. Refusing to '
                . 'delete them, because a media directory that has become empty, for example a '
                . 'bind mount whose backing filesystem went away, is indistinguishable from a '
                . 'library that was emptied on purpose. If it really was emptied, the rows '
                . 'will be removed by the next scan that finds any file.',
            );

            return;
        }

        Song::deleteWhereValueNotIn($paths, 'path', static function (Builder $builder): Builder {
            return $builder->whereNull('podcast_id');
        });

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
     *
     * The predicate deliberately mirrors the delete below rather than testing storage type
     * directly: SongStorageType::LOCAL is the empty string while the column is frequently null,
     * so asking "would this delete anything" is both simpler and harder to get wrong.
     */
    private function wouldDeleteEverythingOnAnEmptyScan(MediaScanCompleted $event, array $paths): bool
    {
        if ($event->results->valid()->isNotEmpty()) {
            return false;
        }

        return Song::query()->whereNull('podcast_id')->whereNotIn('path', $paths)->exists();
    }
}
