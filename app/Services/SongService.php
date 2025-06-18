<?php

namespace App\Services;

use App\Facades\License;
use App\Jobs\DeleteSongFiles;
use App\Jobs\DeleteTranscodeFiles;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\Transcode;
use App\Repositories\SongRepository;
use App\Repositories\TranscodeRepository;
use App\Values\SongFileInfo;
use App\Values\SongUpdateData;
use App\Values\Transcoding\TranscodeFileInfo;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SongService
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly TranscodeRepository $transcodeRepository,
    ) {
    }

    /** @return Collection<array-key, Song> */
    public function updateSongs(array $ids, SongUpdateData $data): Collection
    {
        if (count($ids) === 1) {
            // If we're only updating one song, an empty non-required should be converted to the default values.
            // This allows the user to clear those fields.
            $data->disc = $data->disc ?: 1;
            $data->track = $data->track ?: 0;
            $data->lyrics = $data->lyrics ?: '';
            $data->year = $data->year ?: null;
            $data->genre = $data->genre ?: '';
            $data->albumArtistName = $data->albumArtistName ?: $data->artistName;
        }

        return DB::transaction(function () use ($ids, $data): Collection {
            $multiSong = count($ids) > 1;
            $noTrackUpdate = $multiSong && !$data->track;

            return collect($ids)
                ->reduce(function (Collection $updated, string $id) use ($data, $noTrackUpdate): Collection {
                    $foundSong = Song::query()->with('album.artist')->find($id);

                    if ($noTrackUpdate) {
                        $data->track = $foundSong->track;
                    }

                    optional(
                        $foundSong,
                        fn (Song $song) => $updated->push($this->updateSong($song, clone $data)) // @phpstan-ignore-line
                    );

                    if ($noTrackUpdate) {
                        $data->track = null;
                    }

                    return $updated;
                }, collect());
        });
    }

    private function updateSong(Song $song, SongUpdateData $data): Song
    {
        // for non-nullable fields, if the provided data is empty, use the existing value
        $data->albumName = $data->albumName ?: $song->album->name;
        $data->artistName = $data->artistName ?: $song->artist->name;
        $data->title = $data->title ?: $song->title;

        // For nullable fields, use the existing value only if the provided data is explicitly null.
        // This allows us to clear those fields.
        $data->albumArtistName ??= $song->album_artist->name;
        $data->lyrics ??= $song->lyrics;
        $data->track ??= $song->track;
        $data->disc ??= $song->disc;
        $data->genre ??= $song->genre;
        $data->year ??= $song->year;

        $albumArtist = Artist::getOrCreate($song->album_artist->user, $data->albumArtistName);
        $artist = Artist::getOrCreate($song->artist->user, $data->artistName);
        $album = Album::getOrCreate($albumArtist, $data->albumName);

        $song->album_id = $album->id;
        $song->artist_id = $artist->id;
        $song->title = $data->title;
        $song->lyrics = $data->lyrics;
        $song->track = $data->track;
        $song->disc = $data->disc;
        $song->genre = $data->genre;
        $song->year = $data->year;

        $song->push();

        return $this->songRepository->getOne($song->id);
    }

    public function markSongsAsPublic(EloquentCollection $songs): void
    {
        $songs->toQuery()->update(['is_public' => true]);
    }

    /** @return array<string> IDs of songs that are marked as private */
    public function markSongsAsPrivate(EloquentCollection $songs): array
    {
        License::requirePlus();

        // Songs that are in collaborative playlists can't be marked as private.
        /**
         * @var Collection<array-key, Song> $collaborativeSongs
         */
        $collaborativeSongs = $songs->toQuery()
            ->join('playlist_song', 'songs.id', '=', 'playlist_song.song_id')
            ->join('playlist_user', 'playlist_song.playlist_id', '=', 'playlist_user.playlist_id')
            ->select('songs.id')
            ->distinct()
            ->pluck('songs.id')
            ->all();

        $applicableSongIds = $songs->whereNotIn('id', $collaborativeSongs)->modelKeys();

        Song::query()->whereKey($applicableSongIds)->update(['is_public' => false]);

        return $applicableSongIds;
    }

    /**
     * @param array<string>|string $ids
     */
    public function deleteSongs(array|string $ids): void
    {
        $ids = Arr::wrap($ids);

        // Since song (and cascadingly, transcode) records will be deleted, we query them first and, if there are any,
        // dispatch a job to delete their associated files.
        $songFiles = Song::query()
            ->findMany($ids)
            ->map(static fn (Song $song) => SongFileInfo::fromSong($song)); // @phpstan-ignore-line

        $transcodeFiles = $this->transcodeRepository->findBySongIds($ids)
            ->map(static fn (Transcode $transcode) => TranscodeFileInfo::fromTranscode($transcode)); // @phpstan-ignore-line

        if (Song::destroy($ids) === 0) {
            return;
        }

        DeleteSongFiles::dispatch($songFiles);

        if ($transcodeFiles->isNotEmpty()) {
            DeleteTranscodeFiles::dispatch($transcodeFiles);
        }
    }
}
