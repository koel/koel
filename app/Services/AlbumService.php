<?php

namespace App\Services;

use App\Exceptions\AlbumNameConflictException;
use App\Models\Album;
use App\Repositories\AlbumRepository;
use App\Services\Image\ImageStorage;
use App\Values\Album\AlbumUpdateData;
use App\Values\ImageWritingConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Finder\Finder;

class AlbumService
{
    public function __construct(
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
            $matches = array_keys(iterator_to_array(
                $this->finder::create()
                    ->depth(0)
                    ->ignoreUnreadableDirs()
                    ->files()
                    ->followLinks()
                    ->name('/(cov|fold)er\.(jpe?g|gif|png|webp|avif)$/i')
                    ->in($directory),
            ));

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
            source: $this->imageStorage->get($album->cover),
            config: ImageWritingConfig::make(maxWidth: 48, blur: 10),
            fileName: $album->thumbnail,
        );

        return $album->thumbnail;
    }

    public function getOrCreateAlbumThumbnail(Album $album): ?string
    {
        if ($album->thumbnail && !$this->imageStorage->exists($album->thumbnail)) {
            $this->generateAlbumThumbnail($album);
        }

        return $album->thumbnail;
    }
}
