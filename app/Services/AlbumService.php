<?php

namespace App\Services;

use App\Exceptions\AlbumNameConflictException;
use App\Models\Album;
use App\Repositories\AlbumRepository;
use App\Services\Image\ImageStorage;
use App\Values\Album\AlbumUpdateData;
use App\Values\ImageWritingConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class AlbumService
{
    public function __construct(
        private readonly RichTextSanitizer $richTextSanitizer,
        private readonly AlbumRepository $albumRepository,
        private readonly ImageStorage $imageStorage,
        private readonly Finder $finder,
    ) {}

    public function updateAlbum(Album $album, AlbumUpdateData $dto): Album
    {
        // Ensure that the album name is unique within the artist
        $existingAlbumWithTheSameName = $this->albumRepository->findOneBy([
            'name' => $dto->name,
            'artist_id' => $album->artist_id,
        ]);

        throw_if($existingAlbumWithTheSameName?->isNot($album), AlbumNameConflictException::class);

        $data = $this->imageStorage->storeOptionalImage($dto->cover)->applyToAttributes($dto->toArray(), 'cover');

        $data = $this->richTextSanitizer->applySanitizedToAttributes($data, 'description', $dto->description);

        $album->update($data);

        return $album->refresh();
    }

    public function storeAlbumCover(Album $album, mixed $source): ?string
    {
        $fileName = $this->imageStorage->storeImage($source);
        $album->cover = $fileName;
        $album->save();

        return $fileName;
    }

    public function trySetAlbumCoverFromDirectory(Album $album, string $directory): void
    {
        $cover = $this->findCachedCoverInDirectory($directory);

        if ($cover && !File::exists($cover)) {
            Cache::forget(cache_key($directory, 'cover'));
            $cover = $this->findCachedCoverInDirectory($directory);
        }

        if ($cover && is_image($cover)) {
            $this->storeAlbumCover($album, $cover);
        }
    }

    private function findCachedCoverInDirectory(string $directory): ?string
    {
        // As directory scanning can be expensive, we cache and reuse the result.
        return Cache::remember(cache_key($directory, 'cover'), now()->addDay(), function () use ($directory): ?string {
            $matches = array_keys(iterator_to_array(
                $this->finder::create()
                    ->depth(0)
                    ->ignoreUnreadableDirs()
                    ->files()
                    ->followLinks()
                    ->name('/(cov|fold)er\.(jpe?g|gif|png|webp|avif)$/i')
                    ->in($directory),
            ));

            return $matches[0] ?? null;
        });
    }

    public function generateAlbumThumbnail(Album $album): string
    {
        $this->imageStorage->storeImage(
            source: $this->imageStorage->get($album->cover),
            config: ImageWritingConfig::make(maxWidth: 48, blur: 10),
            fileName: $album->thumbnail,
        );

        return $album->thumbnail;
    }

    public function generateAlbumSmallCover(Album $album): void
    {
        $album->small_cover = $this->imageStorage->storeImage(
            source: $this->imageStorage->get($album->cover),
            config: ImageWritingConfig::make(maxWidth: 144),
        );

        $album->saveQuietly();
    }

    public function getOrCreateAlbumThumbnail(Album $album): ?string
    {
        if ($album->thumbnail && !$this->imageStorage->exists($album->thumbnail)) {
            $this->generateAlbumThumbnail($album);
        }

        return $album->thumbnail;
    }
}
