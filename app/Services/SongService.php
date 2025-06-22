<?php

namespace App\Services;

use App\Events\SongFolderStructureExtractionRequested;
use App\Facades\License;
use App\Jobs\DeleteSongFiles;
use App\Jobs\DeleteTranscodeFiles;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\Transcode;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Repositories\TranscodeRepository;
use App\Services\Scanners\Contracts\ScannerCacheStrategy as CacheStrategy;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanInformation;
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
        private readonly MediaMetadataService $mediaMetadataService,
        private readonly CacheStrategy $cache,
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
                        $data->track = $foundSong?->track;
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

    public function createOrUpdateSongFromScan(ScanInformation $info, ScanConfiguration $config): Song
    {
        /** @var ?Song $song */
        $song = Song::query()->where('path', $info->path)->first();

        $isFileNew = !$song;
        $isFileModified = $song && $song->mtime !== $info->mTime;
        $isFileNewOrModified = $isFileNew || $isFileModified;

        // if the file is not new or modified and we're not force-rescanning, skip the whole process.
        if (!$isFileNewOrModified && !$config->force) {
            return $song;
        }

        $data = $info->toArray();

        // If the file is new, we take all necessary metadata, totally discarding the "ignores" config.
        // Otherwise, we only take the metadata not in the "ignores" config.
        if (!$isFileNew) {
            Arr::forget($data, $config->ignores);
        }

        $artist = $this->resolveArtist($config->owner, Arr::get($data, 'artist'));

        $albumArtist = Arr::get($data, 'albumartist')
            ? $this->resolveArtist($config->owner, $data['albumartist'])
            : $artist;

        $album = $this->resolveAlbum($albumArtist, Arr::get($data, 'album'));

        if (!$album->has_cover && !in_array('cover', $config->ignores, true)) {
            $coverData = Arr::get($data, 'cover.data');

            if ($coverData) {
                $this->mediaMetadataService->writeAlbumCover($album, $coverData);
            } else {
                $this->mediaMetadataService->trySetAlbumCoverFromDirectory($album, dirname($data['path']));
            }
        }

        Arr::forget($data, ['album', 'artist', 'albumartist', 'cover']);

        $data['album_id'] = $album->id;
        $data['artist_id'] = $artist->id;
        $data['is_public'] = $config->makePublic;

        if ($isFileNew) {
            // Only set the owner if the song is new, i.e., don't override the owner if the song is being updated.
            $data['owner_id'] = $config->owner->id;
            $song = Song::query()->create($data);
        } else {
            $song->update($data);
        }

        if (!$album->year && $song->year) {
            $album->update(['year' => $song->year]);
        }

        if ($config->extractFolderStructure) {
            event(new SongFolderStructureExtractionRequested($song));
        }

        return $song;
    }

    private function resolveArtist(User $user, ?string $name): Artist
    {
        $name = trim($name);

        return $this->cache->remember(
            key: cache_key(__METHOD__, $user->id, $name),
            ttl: now()->addMinutes(30),
            callback: static fn () => Artist::getOrCreate($user, $name)
        );
    }

    private function resolveAlbum(Artist $artist, ?string $name): Album
    {
        $name = trim($name);

        return $this->cache->remember(
            key: cache_key(__METHOD__, $artist->id, $name),
            ttl: now()->addMinutes(30),
            callback: static fn () => Album::getOrCreate($artist, $name)
        );
    }
}
