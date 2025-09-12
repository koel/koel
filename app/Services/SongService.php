<?php

namespace App\Services;

use App\Events\LibraryChanged;
use App\Facades\Dispatcher;
use App\Facades\License;
use App\Jobs\DeleteSongFilesJob;
use App\Jobs\DeleteTranscodeFilesJob;
use App\Jobs\ExtractSongFolderStructureJob;
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
use App\Values\Song\SongFileInfo;
use App\Values\Song\SongUpdateData;
use App\Values\Song\SongUpdateResult;
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
        private readonly ImageStorage $imageStorage,
        private readonly CacheStrategy $cache,
    ) {
    }

    public function updateSongs(array $ids, SongUpdateData $data): SongUpdateResult
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

        return DB::transaction(function () use ($ids, $data): SongUpdateResult {
            $result = SongUpdateResult::make();
            $multiSong = count($ids) > 1;
            $noTrackUpdate = $multiSong && !$data->track;
            $affectedAlbums = collect();
            $affectedArtists = collect();

            Song::query()->with('artist.user', 'album.artist', 'album.artist.user')->findMany($ids)->each(
                function (Song $song) use ($data, $result, $noTrackUpdate, $affectedAlbums, $affectedArtists): void {
                    if ($noTrackUpdate) {
                        $data->track = $song->track;
                    }

                    if ($affectedAlbums->pluck('id')->doesntContain($song->album_id)) {
                        $affectedAlbums->push($song->album);
                    }

                    if ($affectedArtists->pluck('id')->doesntContain($song->artist_id)) {
                        $affectedArtists->push($song->artist);
                    }

                    if ($affectedArtists->pluck('id')->doesntContain($song->album->artist_id)) {
                        $affectedArtists->push($song->album_artist);
                    }

                    $result->addSong($this->updateSong($song, clone $data)); // @phpstan-ignore-line

                    if ($noTrackUpdate) {
                        $data->track = null;
                    }
                },
            );

            $affectedAlbums->each(static function (Album $album) use ($result): void {
                if ($album->refresh()->songs()->count() === 0) {
                    $result->addRemovedAlbum($album);
                    $album->delete();
                }
            });

            $affectedArtists->each(static function (Artist $artist) use ($result): void {
                if ($artist->refresh()->songs()->count() === 0) {
                    $result->addRemovedArtist($artist);
                    $artist->delete();
                }
            });

            return $result;
        });
    }

    private function updateSong(Song $song, SongUpdateData $data): Song
    {
        // For non-nullable fields, if the provided data is empty, use the existing value
        $data->albumName = $data->albumName ?: $song->album->name;
        $data->artistName = $data->artistName ?: $song->artist->name;
        $data->title = $data->title ?: $song->title;

        // For nullable fields, use the existing value only if the provided data is explicitly null
        // (i.e., when multiple songs are being updated and the user did not provide a value).
        // This allows us to clear those fields (when user provides an empty string).
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
        $song->album_name = $album->name;
        $song->artist_id = $artist->id;
        $song->artist_name = $artist->name;
        $song->title = $data->title;
        $song->lyrics = $data->lyrics;
        $song->track = $data->track;
        $song->disc = $data->disc;
        $song->year = $data->year;

        $song->push();

        if (!$song->genreEqualsTo($data->genre)) {
            $song->syncGenres($data->genre);
        }

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

        Dispatcher::dispatch(new DeleteSongFilesJob($songFiles));

        if ($transcodeFiles->isNotEmpty()) {
            Dispatcher::dispatch(new DeleteTranscodeFilesJob($transcodeFiles));
        }

        // Instruct the system to prune the library, i.e., remove empty albums and artists.
        event(new LibraryChanged());
    }

    public function createOrUpdateSongFromScan(ScanInformation $info, ScanConfiguration $config): Song
    {
        /** @var ?Song $song */
        $song = Song::query()->where('path', $info->path)->first();

        $isFileNew = !$song;
        $isFileModified = $song && $song->isFileModified($info);
        $isFileNewOrModified = $isFileNew || $isFileModified;

        if (!$isFileNewOrModified && !$config->force) {
            return $song;
        }

        $data = $info->toArray();
        $genre = Arr::pull($data, 'genre', '');

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
                $this->imageStorage->storeAlbumCover($album, $coverData);
            } else {
                $this->imageStorage->trySetAlbumCoverFromDirectory($album, dirname($data['path']));
            }
        }

        Arr::forget($data, ['album', 'artist', 'albumartist', 'cover']);

        $data['album_id'] = $album->id;
        $data['artist_id'] = $artist->id;
        $data['is_public'] = $config->makePublic;
        $data['album_name'] = $album->name;
        $data['artist_name'] = $artist->name;

        if ($isFileNew) {
            // Only set the owner if the song is new, i.e., don't override the owner if the song is being updated.
            $data['owner_id'] = $config->owner->id;
            /** @var Song $song */
            $song = Song::query()->create($data);
        } else {
            $song->update($data);
        }

        if ($genre !== $song->genre) {
            $song->syncGenres($genre);
        }

        if (!$album->year && $song->year) {
            $album->update(['year' => $song->year]);
        }

        if ($config->extractFolderStructure) {
            Dispatcher::dispatch(new ExtractSongFolderStructureJob($song));
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
