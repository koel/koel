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
use App\Models\User;
use App\Repositories\SongRepository;
use App\Repositories\TranscodeRepository;
use App\Services\Image\ImageStorage;
use App\Services\Scanners\Contracts\ScannerCacheStrategy as CacheStrategy;
use App\Services\SongStorages\SongStorageFactory;
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
use Illuminate\Support\LazyCollection;

// @mago-ignore lint:cyclomatic-complexity
// @mago-ignore lint:too-many-methods,kan-defect
class SongService
{
    private const int DELETION_BATCH_SIZE = 500;

    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly TranscodeRepository $transcodeRepository,
        private readonly AlbumService $albumService,
        private readonly ImageStorage $imageStorage,
        private readonly CacheStrategy $cache,
        private readonly LibrarySummaryService $librarySummaryService,
    ) {}

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
        }

        return DB::transaction(function () use ($ids, $data): SongUpdateResult {
            $result = SongUpdateResult::make();
            $multiSong = count($ids) > 1;
            $noTrackUpdate = $multiSong && !$data->track;
            $affectedAlbums = collect();
            $affectedArtists = collect();

            Song::query()
                ->with('artist.user', 'album.artist', 'album.artist.user')
                ->findMany($ids)
                ->each(function (Song $song) use (
                    $data,
                    $result,
                    $noTrackUpdate,
                    $affectedAlbums,
                    $affectedArtists,
                ): void {
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
                });

            $affectedAlbums->each(static function (Album $album) use ($result): void {
                if ($album->refresh()->songs()->count() === 0) {
                    $result->addRemovedAlbum($album);
                    $album->delete();
                }
            });

            $affectedArtists->each(static function (Artist $artist) use ($result): void {
                if ($artist->songs()->count() === 0 && $artist->albums()->count() === 0) {
                    $result->addRemovedArtist($artist);
                    $artist->delete();
                }
            });

            return $result;
        });
    }

    // @mago-ignore lint:halstead
    private function updateSong(Song $song, SongUpdateData $data): Song
    {
        // For non-nullable fields, if the provided data is empty, use the existing value
        $data->albumName = $data->albumName ?: $song->album->name;
        $data->artistName = $data->artistName ?: $song->artist->name;
        $data->title = $data->title ?: $song->title;

        // For nullable fields, use the existing value only if the provided data is explicitly null
        // (i.e., when multiple songs are being updated and the user did not provide a value).
        // This allows us to clear those fields (when the user provides an empty string).
        // Album artist handling:
        // An explicit album artist is used as-is, and an empty one (the user cleared it) means
        // "same as the song artist". When none is given, a compilation (album artist ≠ song artist)
        // keeps its album artist (e.g. "Various Artists"), and any other song follows its artist.
        if ($data->albumArtistName === null) {
            $isCompilation = !$song->album_artist->is($song->artist);
            $data->albumArtistName = $isCompilation ? $song->album_artist->name : $data->artistName;
        } elseif ($data->albumArtistName === '') {
            $data->albumArtistName = $data->artistName;
        }
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
            $this->librarySummaryService->markLibraryChanged();
        }

        return $this->songRepository->getOne($song->id);
    }

    public function markSongsAsPublic(EloquentCollection $songs): void
    {
        $songs->toQuery()->update(['is_public' => true]);

        $this->librarySummaryService->markLibraryChanged();
    }

    /** @return array<string> IDs of songs that are marked as private */
    public function markSongsAsPrivate(EloquentCollection $songs): array
    {
        License::requirePlus();

        // Songs that are in collaborative playlists can't be marked as private.
        /**
         * @var Collection<array-key, Song> $collaborativeSongs
         */
        $collaborativeSongs = $songs
            ->toQuery()
            ->join('playlist_song', 'songs.id', '=', 'playlist_song.song_id')
            ->join('playlist_user', 'playlist_song.playlist_id', '=', 'playlist_user.playlist_id')
            ->where('playlist_user.role', 'collaborator')
            ->select('songs.id')
            ->distinct()
            ->pluck('songs.id')
            ->all();

        $applicableSongIds = $songs->whereNotIn('id', $collaborativeSongs)->values()->modelKeys();

        Song::query()->whereKey($applicableSongIds)->update(['is_public' => false]);

        $this->librarySummaryService->markLibraryChanged();

        return $applicableSongIds;
    }

    /**
     * @return bool Whether the file was found and its size recorded.
     */
    public function fillMissingFileSize(Song $song): bool
    {
        $fileSize = SongStorageFactory::make($song->storage)->getFileSize($song->storage_metadata->getPath());

        if ($fileSize === null) {
            return false;
        }

        $song->updateQuietly(['file_size' => $fileSize]);

        return true;
    }

    public function deleteSongsByUser(User $user): void
    {
        $this->deleteSongsInBatches($this->songRepository->lazyGetIdsByOwner($user));
    }

    /**
     * @param array<string>|string $ids
     */
    public function deleteSongs(array|string $ids): void
    {
        $this->deleteSongsInBatches(LazyCollection::make(Arr::wrap($ids)));
    }

    /**
     * @param LazyCollection<int, string> $ids
     */
    private function deleteSongsInBatches(LazyCollection $ids): void
    {
        $deletedCount = $ids->chunk(self::DELETION_BATCH_SIZE)->sum(
            fn (LazyCollection $batchIds): int => $this->deleteSongsAndQueueFileRemoval($batchIds->all()),
        );

        if ($deletedCount > 0) {
            // Instruct the system to prune the library, i.e., remove empty albums and artists.
            event(new LibraryChanged());
        }
    }

    /**
     * @param array<string> $ids
     */
    private function deleteSongsAndQueueFileRemoval(array $ids): int
    {
        // Since song (and cascadingly, transcode) records will be deleted, we query them first and, if there are any,
        // dispatch a job to delete their associated files.
        $songs = $this->songRepository->getManyForFileRemoval($ids);

        $transcodeFiles = $this->transcodeRepository->findBySongIds($ids)->map(TranscodeFileInfo::fromTranscode(...)); // @phpstan-ignore-line

        $deletedCount = Song::query()->whereKey($songs->modelKeys())->delete();

        if ($deletedCount === 0) {
            return 0;
        }

        (new Song())->queueRemoveFromSearch($songs);

        Dispatcher::dispatch(new DeleteSongFilesJob($songs->map(SongFileInfo::fromSong(...))));

        $transcodeFiles->whenNotEmpty(static function (Collection $files): void {
            Dispatcher::dispatch(new DeleteTranscodeFilesJob($files));
        });

        return $deletedCount;
    }

    public function createOrUpdateSongFromScan(ScanInformation $info, ScanConfiguration $config): ?Song
    {
        $song = $this->songRepository->findOneByPath($info->path);

        $isFileNew = !$song;
        $isFileModified = $song && $song->isFileModified($info->mTime);
        $isFileNewOrModified = $isFileNew || $isFileModified;

        if (!$isFileNewOrModified && !$config->force) {
            return $song;
        }

        $data = $info->toArray();
        $genre = Arr::pull($data, 'genre', '');

        // If the file is new, we take all necessary metadata, totally discarding the "ignores" config.
        // Otherwise, we only take the metadata not in the "ignores" config.
        if (!$isFileNew) {
            Arr::forget($data, [...$config->ignores, ...self::getMbidKeysOfIgnoredNames($config->ignores)]);
            $data += self::getCurrentArtistAndAlbumNames($song);
        }

        $libraryOwner = $isFileNew ? $config->owner : $song->loadMissing('owner')->owner;
        $artist = $this->resolveArtist($libraryOwner, Arr::get($data, 'artist'));

        $albumArtist = Arr::get($data, 'albumartist')
            ? $this->resolveArtist($libraryOwner, $data['albumartist'])
            : $artist;

        $album = $this->resolveAlbum($albumArtist, Arr::get($data, 'album'));

        $artist->setMbidIfMissing(Arr::get($data, 'artist_mbid'));
        $albumArtist->setMbidIfMissing(Arr::get($data, 'albumartist_mbid'));
        $album->setMbidIfMissing(Arr::get($data, 'album_mbid'));

        $hasCover = $this->imageStorage->exists($album->cover);

        if (!$hasCover && !in_array('cover', $config->ignores, true)) {
            $coverData = Arr::get($data, 'cover.data');

            if ($coverData) {
                rescue(fn () => $this->albumService->storeAlbumCover($album, $coverData), report: true);
            } else {
                $this->albumService->trySetAlbumCoverFromDirectory($album, dirname($data['path']));
            }
        }

        Arr::forget($data, [
            'album',
            'artist',
            'albumartist',
            'cover',
            'album_mbid',
            'artist_mbid',
            'albumartist_mbid',
        ]);

        $data['album_id'] = $album->id;
        $data['artist_id'] = $artist->id;
        $data['album_name'] = $album->name;
        $data['artist_name'] = $artist->name;

        if ($isFileNew) {
            // Only set the owner and visibility if the song is new, i.e., don't override them on an update.
            $data['owner_id'] = $config->owner->id;
            $data['is_public'] = $config->makePublic;
            /** @var Song $song */
            $song = Song::query()->create($data);
        } else {
            $song->update($data);
        }

        $keepsCurrentGenre = !$isFileNew && in_array('genre', $config->ignores, true);

        if (!$keepsCurrentGenre && $genre !== $song->genre) {
            $song->syncGenres($genre);
        }

        if (!$album->year && $song->year) {
            $album->update(['year' => $song->year]);
        }

        if ($config->extractFolderStructure && !$song->folder_id) {
            Dispatcher::dispatch(new ExtractSongFolderStructureJob($song));
        }

        return $song;
    }

    /**
     * @param array<string> $ignores
     *
     * @return array<string>
     */
    private static function getMbidKeysOfIgnoredNames(array $ignores): array
    {
        return array_values(Arr::only([
            'artist' => 'artist_mbid',
            'albumartist' => 'albumartist_mbid',
            'album' => 'album_mbid',
        ], $ignores));
    }

    /**
     * @return array{artist: string, albumartist: string, album: string}
     */
    private static function getCurrentArtistAndAlbumNames(Song $song): array
    {
        return [
            'artist' => $song->artist->name,
            'albumartist' => $song->album_artist->name,
            'album' => $song->album->name,
        ];
    }

    private function resolveArtist(User $user, ?string $name): Artist
    {
        $name = trim($name);

        return $this->cache->remember(
            key: cache_key(__METHOD__, $user->id, $name),
            callback: static fn () => Artist::getOrCreate($user, $name),
        );
    }

    private function resolveAlbum(Artist $artist, ?string $name): Album
    {
        $name = trim($name);

        return $this->cache->remember(
            key: cache_key(__METHOD__, $artist->id, $name),
            callback: static fn () => Album::getOrCreate($artist, $name),
        );
    }
}
