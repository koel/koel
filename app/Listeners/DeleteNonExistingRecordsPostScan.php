<?php

namespace App\Listeners;

use App\Events\MediaScanCompleted;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\LibraryManager;
use App\Values\Scanning\ScanResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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

        if ($this->wouldDeleteTooMuch($paths)) {
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

    /**
     * The empty-scan guard above covers a scan that finds NOTHING. A scan that finds SOME of the
     * library is the commoner fault and is not covered by it: ignoreUnreadableDirs() reports a
     * subtree the scanner cannot enter as simply absent, so a scan seeing 1 file of 157 would
     * delete the other 156 rows and then prune the albums and artists they leave empty.
     *
     * Refuses a deletion that would remove more than the configured SHARE of the non-podcast
     * library (`koel.scan.max_deletion_ratio`, a fraction between 0 and 1). Unset is off, which
     * keeps existing behaviour for anyone who never sets it; exactly 1 is the explicit way to
     * disable it. Anything else that cannot be read as a share, an empty value, a non-number,
     * `20` meant as twenty percent, refuses EVERY deletion and logs why, because a bound that
     * cannot be read is not a bound and must not fail open.
     */
    private function wouldDeleteTooMuch(array $paths): bool
    {
        $ratio = config('koel.scan.max_deletion_ratio');

        // Unset (null) is off. An empty string is NOT unset: `RATIO=` in a compose file, or a
        // `${VAR}` that resolves to nothing, arrives here as '' and is refused below.
        if ($ratio === null) {
            return false;
        }

        if (!is_numeric($ratio) || (float) $ratio < 0.0 || (float) $ratio > 1.0) {
            Log::error(sprintf('koel.scan.max_deletion_ratio is %s, which is not a share between 0 and 1. Refusing '
            . 'every scan deletion until it is corrected. Use 0.2 for twenty percent, or unset it.', var_export(
                $ratio,
                true,
            )));

            return true;
        }

        if ((float) $ratio === 1.0) {
            return false;
        }

        // Only locally stored songs can be deleted by a scan, so they are the population; a
        // cloud song is merged into $paths and never doomed, and counting it would dilute the
        // share.
        $total = Song::query()->storedLocally()->count();

        if ($total === 0) {
            return false;
        }

        // Count the way deleteWhereValueNotIn() deletes, in both of its regimes, so the guard
        // measures what the delete would do: under the parameter limit, a SQL whereNotIn,
        // which compares under the connection's collation (case- and accent-insensitive on
        // MySQL's default); above it, the byte-exact array_diff the trait itself falls back
        // to. Strictly below the limit, because storedLocally() binds one parameter of its
        // own. A count taken any other way can disagree with the delete it is bounding.
        $maxChunkSize = DB::getDriverName() === 'sqlite' ? 999 : 65_535;

        $doomed = count($paths) < $maxChunkSize
            ? Song::query()->storedLocally()->whereNotIn('path', $paths)->count()
            : count(array_diff(Song::query()->storedLocally()->pluck('path')->all(), $paths));

        if (($doomed / $total) <= (float) $ratio) {
            return false;
        }

        Log::warning(sprintf(
            'Scan would delete %d of %d songs, more than the %s share allowed by '
            . 'koel.scan.max_deletion_ratio. Refusing, because a partially readable media directory is '
            . 'indistinguishable from a library that really shrank. Raise or unset the setting if the '
            . 'deletion is intended.',
            $doomed,
            $total,
            $ratio,
        ));

        return true;
    }
}
