<?php

namespace App\Services\Integrations;

use App\Models\Album;
use App\Models\Artist;
use App\Pipelines\Encyclopedia\GetAlbumTracksUsingMbid;
use App\Pipelines\Encyclopedia\GetMbidForArtist;
use App\Pipelines\Encyclopedia\GetReleaseAndReleaseGroupMbidsForAlbum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Str;

/**
 * Fetches MusicBrainz identifiers for albums and artists and stores the ones they are missing.
 *
 * This runs whenever MusicBrainz is enabled, independently of which service currently supplies encyclopedia
 * entries — identifiers are useful regardless of who writes the prose. The lookups behind them are cached
 * indefinitely, so asking again for an album or artist already seen costs nothing.
 */
class MbidService
{
    public function fetchAndStoreArtistMbid(Artist $artist): void
    {
        if ($artist->is_unknown || $artist->is_various) {
            return;
        }

        rescue_if(MusicBrainzService::enabled(), static function () use ($artist): void {
            /** @var string|null $mbid */
            $mbid = Pipeline::send($artist->name)->through([GetMbidForArtist::class])->thenReturn();

            $artist->setMbidIfMissing($mbid);
        });
    }

    public function fetchAndStoreAlbumMbids(Album $album): void
    {
        if ($album->is_unknown || $album->artist->is_unknown) {
            return;
        }

        rescue_if(MusicBrainzService::enabled(), static function () use ($album): void {
            /** @var array{0: ?string, 1: ?string} $mbids */
            $mbids = Pipeline::send([
                'album' => $album->name,
                'artist' => $album->artist->name,
            ])->through([GetReleaseAndReleaseGroupMbidsForAlbum::class])->thenReturn();

            $albumMbid = $mbids[0] ?? null;

            if (!$albumMbid) {
                return;
            }

            $album->setMbidIfMissing($albumMbid);

            /** @var array<mixed> $tracks */
            $tracks = Pipeline::send($albumMbid)->through([GetAlbumTracksUsingMbid::class])->thenReturn() ?: [];

            self::storeRecordingMbids($album, $tracks);
        });
    }

    /** @param array<mixed> $tracks The release's tracks, as returned by MusicBrainz */
    private static function storeRecordingMbids(Album $album, array $tracks): void
    {
        $recordingMbids = self::getRecordingMbidsByTitle($tracks);

        if ($recordingMbids->isEmpty()) {
            return;
        }

        foreach ($album->songs as $song) {
            $song->setMbidIfMissing($recordingMbids->get(self::normalizeTitle($song->title)));
        }
    }

    /**
     * Songs are matched to recordings by title, which doubles as the safety check: a release the name search
     * got wrong shares no titles with the album, so nothing is stored. Titles appearing more than once can't be
     * matched to a single recording, so they are dropped.
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
