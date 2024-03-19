<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Values\ScanConfiguration;
use App\Values\ScanResult;
use App\Values\SongScanInformation;
use getID3;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Arr;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class FileScanner
{
    private ?int $fileModifiedTime = null;
    private ?string $filePath = null;

    /**
     * The song model that's associated with the current file.
     */
    private ?Song $song;

    private ?string $syncError = null;

    public function __construct(
        private getID3 $getID3,
        private MediaMetadataService $mediaMetadataService,
        private SongRepository $songRepository,
        private SimpleLrcReader $lrcReader,
        private Cache $cache,
        private Finder $finder
    ) {
    }

    public function setFile(string|SplFileInfo $path): static
    {
        $file = $path instanceof SplFileInfo ? $path : new SplFileInfo($path);

        $this->filePath = $file->getRealPath();
        $this->song = $this->songRepository->findOneByPath($this->filePath);
        $this->fileModifiedTime = Helper::getModifiedTime($file);

        return $this;
    }

    public function getScanInformation(): ?SongScanInformation
    {
        $raw = $this->getID3->analyze($this->filePath);
        $this->syncError = Arr::get($raw, 'error.0') ?: (Arr::get($raw, 'playtime_seconds') ? null : 'Empty file');

        if ($this->syncError) {
            return null;
        }

        $this->getID3->CopyTagsToComments($raw);
        $info = SongScanInformation::fromGetId3Info($raw, $this->filePath);

        $info->lyrics = $info->lyrics ?: $this->lrcReader->tryReadForMediaFile($this->filePath);

        return $info;
    }

    public function scan(ScanConfiguration $config): ScanResult
    {
        if (!$this->isFileNewOrChanged() && !$config->force) {
            return ScanResult::skipped($this->filePath);
        }

        $info = $this->getScanInformation()?->toArray();

        if (!$info) {
            return ScanResult::error($this->filePath, $this->syncError);
        }

        if (!$this->isFileNew()) {
            Arr::forget($info, $config->ignores);
        }

        $artist = Arr::get($info, 'artist') ? Artist::getOrCreate($info['artist']) : $this->song->artist;
        $albumArtist = Arr::get($info, 'albumartist') ? Artist::getOrCreate($info['albumartist']) : $artist;
        $album = Arr::get($info, 'album') ? Album::getOrCreate($albumArtist, $info['album']) : $this->song->album;

        if (!in_array('cover', $config->ignores, true) && !$album->has_cover) {
            $this->tryGenerateAlbumCover($album, Arr::get($info, 'cover', []));
        }

        $data = Arr::except($info, ['album', 'artist', 'albumartist', 'cover']);
        $data['album_id'] = $album->id;
        $data['artist_id'] = $artist->id;
        $data['is_public'] = $config->makePublic;

        if ($this->isFileNew()) {
            // Only set the owner if the song is new i.e. don't override the owner if the song is being updated.
            $data['owner_id'] = $config->owner->id;
        }

        // @todo Decouple song creation from scanning.
        $this->song = Song::query()->updateOrCreate(['path' => $this->filePath], $data); // @phpstan-ignore-line

        return ScanResult::success($this->filePath);
    }

    /**
     * Try to generate a cover for an album based on extracted data, or use the cover file under the directory.
     *
     * @param ?array<mixed> $coverData
     */
    private function tryGenerateAlbumCover(Album $album, ?array $coverData): void
    {
        attempt(function () use ($album, $coverData): void {
            // If the album has no cover, we try to get the cover image from existing tag data
            if ($coverData) {
                $this->mediaMetadataService->writeAlbumCover($album, $coverData['data']);

                return;
            }

            // Or, if there's a cover image under the same directory, use it.
            $cover = $this->getCoverFileUnderSameDirectory();

            if ($cover) {
                $this->mediaMetadataService->writeAlbumCover($album, $cover);
            }
        }, false);
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
        return attempt(static fn () => (bool) exif_imagetype($path)) ?? false;
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

    public function getSong(): ?Song
    {
        return $this->song;
    }
}
