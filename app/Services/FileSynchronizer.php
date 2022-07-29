<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Values\SongScanInformation;
use App\Values\SyncResult;
use getID3;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Arr;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Throwable;

class FileSynchronizer
{
    public const SYNC_RESULT_SUCCESS = 1;
    public const SYNC_RESULT_BAD_FILE = 2;
    public const SYNC_RESULT_UNMODIFIED = 3;

    private ?int $fileModifiedTime = null;
    private ?string $filePath = null;

    /**
     * A (MD5) hash of the file's path.
     * This value is unique, and can be used to query a Song record.
     */
    private ?string $fileHash = null;

    /**
     * The song model that's associated with the current file.
     */
    private ?Song $song;

    private ?string $syncError;

    public function __construct(
        private getID3 $getID3,
        private MediaMetadataService $mediaMetadataService,
        private SongRepository $songRepository,
        private Cache $cache,
        private Finder $finder
    ) {
    }

    public function setFile(string|SplFileInfo $path): self
    {
        $file = $path instanceof SplFileInfo ? $path : new SplFileInfo($path);

        $this->filePath = $file->getPathname();
        $this->fileHash = Helper::getFileHash($this->filePath);
        $this->song = $this->songRepository->getOneById($this->fileHash); // @phpstan-ignore-line
        $this->syncError = null;
        $this->fileModifiedTime = Helper::getModifiedTime($file);

        return $this;
    }

    public function getFileScanInformation(): ?SongScanInformation
    {
        $info = $this->getID3->analyze($this->filePath);
        $this->syncError = Arr::get($info, 'error.0') ?: (Arr::get($info, 'playtime_seconds') ? null : 'Empty file');

        return $this->syncError ? null : SongScanInformation::fromGetId3Info($info);
    }

    /**
     * Sync the song with all available media info into the database.
     *
     * @param array<string> $ignores The tags to ignore/exclude (only taken into account if the song already exists)
     * @param bool $force Whether to force syncing, even if the file is unchanged
     */
    public function sync(array $ignores = [], bool $force = false): SyncResult
    {
        if (!$this->isFileNewOrChanged() && !$force) {
            return SyncResult::skipped($this->filePath);
        }

        $info = $this->getFileScanInformation()?->toArray();

        if (!$info) {
            return SyncResult::error($this->filePath, $this->syncError);
        }

        if (!$this->isFileNew()) {
            Arr::forget($info, $ignores);
        }

        $artist = Arr::get($info, 'artist') ? Artist::getOrCreate($info['artist']) : $this->song->artist;
        $albumArtist = Arr::get($info, 'albumartist') ? Artist::getOrCreate($info['albumartist']) : $artist;
        $album = Arr::get($info, 'album') ? Album::getOrCreate($albumArtist, $info['album']) : $this->song->album;

        if (!$album->has_cover) {
            $this->tryGenerateAlbumCover($album, Arr::get($info, 'cover', []));
        }

        $data = Arr::except($info, ['album', 'artist', 'albumartist', 'cover']);
        $data['album_id'] = $album->id;
        $data['artist_id'] = $artist->id;

        $this->song = Song::updateOrCreate(['id' => $this->fileHash], $data);

        return SyncResult::success($this->filePath);
    }

    /**
     * Try to generate a cover for an album based on extracted data, or use the cover file under the directory.
     *
     * @param array<mixed>|null $coverData
     */
    private function tryGenerateAlbumCover(Album $album, ?array $coverData): void
    {
        try {
            // If the album has no cover, we try to get the cover image from existing tag data
            if ($coverData) {
                $extension = explode('/', $coverData['image_mime']);
                $extension = $extension[1] ?? 'png';

                $this->mediaMetadataService->writeAlbumCover($album, $coverData['data'], $extension);

                return;
            }

            // Or, if there's a cover image under the same directory, use it.
            $cover = $this->getCoverFileUnderSameDirectory();

            if ($cover) {
                $extension = pathinfo($cover, PATHINFO_EXTENSION);
                $this->mediaMetadataService->writeAlbumCover($album, $cover, $extension);
            }
        } catch (Throwable) {
        }
    }

    /**
     * Issue #380.
     * Some albums have its own cover image under the same directory as cover|folder.jpg/png.
     * We'll check if such a cover file is found, and use it if positive.
     */
    private function getCoverFileUnderSameDirectory(): ?string
    {
        // As directory scanning can be expensive, we cache and reuse the result.
        return $this->cache->remember(md5($this->filePath . '_cover'), now()->addDay(), function (): ?string {
            $matches = array_keys(
                iterator_to_array(
                    $this->finder->create()
                        ->depth(0)
                        ->ignoreUnreadableDirs()
                        ->files()
                        ->followLinks()
                        ->name('/(cov|fold)er\.(jpe?g|png)$/i')
                        ->in(dirname($this->filePath))
                )
            );

            $cover = $matches ? $matches[0] : null;

            return $cover && self::isImage($cover) ? $cover : null;
        });
    }

    private static function isImage(string $path): bool
    {
        try {
            return (bool) exif_imagetype($path);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Determine if the file is new (its Song record can't be found in the database).
     */
    public function isFileNew(): bool
    {
        return !$this->song;
    }

    /**
     * Determine if the file is changed (its Song record is found, but the timestamp is different).
     */
    public function isFileChanged(): bool
    {
        return !$this->isFileNew() && $this->song->mtime !== $this->fileModifiedTime;
    }

    public function isFileNewOrChanged(): bool
    {
        return $this->isFileNew() || $this->isFileChanged();
    }

    public function getSyncError(): ?string
    {
        return $this->syncError;
    }

    public function getSong(): ?Song
    {
        return $this->song;
    }
}
