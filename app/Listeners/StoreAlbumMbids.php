<?php

namespace App\Listeners;

use App\Events\AlbumMbidsResolved;
use App\Models\Album;
use App\Models\Song;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StoreAlbumMbids
{
    public function handle(AlbumMbidsResolved $event): void
    {
        $event->album->setMbidIfMissing($event->mbid);

        self::storeRecordingMbids($event->album, self::getRecordingMbidsByTitle($event->tracks));
    }

    /**
     * Songs are matched to recordings by title, which doubles as the safety check: a release the name search
     * got wrong shares no titles with the album, so nothing is stored.
     *
     * @param Collection<array-key, string> $recordingMbids
     */
    private static function storeRecordingMbids(Album $album, Collection $recordingMbids): void
    {
        if ($recordingMbids->isEmpty()) {
            return;
        }

        $album->songs->each(static fn (Song $song) => $song->setMbidIfMissing($recordingMbids->get(self::normalizeTitle($song->title))));
    }

    /**
     * Titles appearing more than once on a release can't be matched to a single recording, so they are dropped.
     *
     * @param array<mixed> $tracks
     *
     * @return Collection<array-key, string>
     */
    private static function getRecordingMbidsByTitle(array $tracks): Collection
    {
        return collect($tracks)
            ->filter(static fn (array $track): bool => (bool) Arr::get($track, 'recording.id'))
            ->groupBy(static fn (array $track): string => self::normalizeTitle(Arr::get($track, 'title')))
            ->reject(static fn (Collection $group): bool => $group->count() > 1)
            ->map(static fn (Collection $group): string => (string) Arr::get($group->first(), 'recording.id'))
            ->except('');
    }

    private static function normalizeTitle(?string $title): string
    {
        return Str::lower(trim($title ?? ''));
    }
}
