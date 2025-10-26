<?php

namespace App\Services;

use App\Exceptions\AlbumNameConflictException;
use App\Models\Album;
use App\Repositories\AlbumRepository;
use App\Values\Album\AlbumUpdateData;
use App\Values\ImageWritingConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class AlbumService
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly ImageStorage $imageStorage,
        private readonly Finder $finder,
    ) {
    }

    public function updateAlbum(Album $album, AlbumUpdateData $dto): Album
    {
        // Ensure that the album name is unique within the artist
        $existingAlbumWithTheSameName = $this->albumRepository->findOneBy([
            'name' => $dto->name,
            'artist_id' => $album->artist_id,
        ]);

        throw_if($existingAlbumWithTheSameName?->isNot($album), AlbumNameConflictException::class);

        $data = $dto->toArray();

        if (is_string($dto->cover)) {
            // A non-empty string means the user is uploading another cover,
            // when an empty string means the user is removing the cover.
            $data['cover'] = rescue_if($dto->cover, fn () => $this->imageStorage->storeImage($dto->cover), '');
        } else {
            // If the cover is null, the user's not changing or removing the cover at all.
            Arr::forget($data, 'cover');
        }

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
        // As directory scanning can be expensive, we cache and reuse the result.
        Cache::remember(cache_key($directory, 'cover'), now()->addDay(), function () use ($album, $directory): ?string {
            $matches = array_keys(
                iterator_to_array(
                    $this->finder::create()
                        ->depth(0)
                        ->ignoreUnreadableDirs()
                        ->files()
                        ->followLinks()
                        ->name('/(cov|fold)er\.(jpe?g|gif|png|webp|avif)$/i')
                        ->in($directory)
                )
            );

            $cover = $matches[0] ?? null;

            if ($cover && is_image($cover)) {
                $this->storeAlbumCover($album, $cover);
            }

            return $cover;
        });
    }

    public function generateAlbumThumbnail(Album $album): string
    {
        $this->imageStorage->storeImage(
            source: image_storage_path($album->cover),
            config: ImageWritingConfig::make(
                maxWidth: 48,
                blur: 10,
            ),
            path: image_storage_path($album->thumbnail),
        );

        return $album->thumbnail;
    }

    public function getOrCreateAlbumThumbnail(Album $album): ?string
    {
        $thumbnailPath = image_storage_path($album->thumbnail);

        if ($thumbnailPath && !File::exists($thumbnailPath)) {
            $this->generateAlbumThumbnail($album);
        }

        return $album->thumbnail;
    }
}
