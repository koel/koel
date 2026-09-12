<?php

namespace App\Services\Integrations;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
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
        if ($artist->is_unknown || $artist->is_various || $artist->mbid) {
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
            $albumMbid = $album->mbid ?: self::searchForReleaseMbid($album);

            if (!$albumMbid) {
                return;
            }

            $album->setMbidIfMissing($albumMbid);

            /** @var array<mixed> $tracks */
            $tracks = Pipeline::send($albumMbid)->through([GetAlbumTracksUsingMbid::class])->thenReturn() ?: [];

            self::storeRecordingMbids($album, $tracks);
        });
    }

    /**
     * A release identifier the tags already carry is authoritative, where this search takes whichever
     * release ranks first for the album and artist names — so only ask when there is nothing to go on.
     */
    private static function searchForReleaseMbid(Album $album): ?string
    {
        /** @var array{0: ?string, 1: ?string} $mbids */
        $mbids = Pipeline::send([
            'album' => $album->name,
            'artist' => $album->artist->name,
        ])->through([GetReleaseAndReleaseGroupMbidsForAlbum::class])->thenReturn();

        return $mbids[0] ?? null;
    }

    /**
     * A title shared by two songs on the same album is as unmatchable as one shared by two tracks on the
     * release: there is no way to tell which is which, and guessing would give both the same recording.
     *
     * @param array<mixed> $tracks The release's tracks, as returned by MusicBrainz
     */
    private static function storeRecordingMbids(Album $album, array $tracks): void
    {
        $recordingMbids = self::getRecordingMbidsByTitle($tracks);

        if ($recordingMbids->isEmpty()) {
            return;
        }

        $songs = $album
            ->songs
            ->groupBy(static fn (Song $song): string => self::normalizeTitle($song->title))
            ->reject(static fn (Collection $group): bool => $group->count() > 1);

        foreach ($songs as $title => $group) {
            $group->first()->setMbidIfMissing($recordingMbids->get($title));
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
