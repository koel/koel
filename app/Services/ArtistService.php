<?php

namespace App\Services;

use App\Exceptions\ArtistNameConflictException;
use App\Models\Artist;
use App\Repositories\ArtistRepository;
use App\Values\Artist\ArtistUpdateData;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class ArtistService
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly ImageStorage $imageStorage,
    ) {
    }

    public function updateArtist(Artist $artist, ArtistUpdateData $dto): Artist
    {
        Assert::false($artist->is_various, '"Various" artists cannot be updated.');

        // Ensure that the artist name is unique (per user)
        $existingArtistWithTheSameName = $this->artistRepository->findOneBy([
            'name' => $dto->name,
            'user_id' => $artist->user_id,
        ]);

        throw_if($existingArtistWithTheSameName?->isNot($artist), ArtistNameConflictException::class);

        $data = $dto->toArray();

        if (is_string($dto->image)) {
            // A non-empty string means the user is uploading another image,
            // when an empty string means the user is removing the image.
            $data['image'] = rescue_if($dto->image, fn () => $this->imageStorage->storeImage($dto->image), '');
        } else {
            // If the image is null, the user's not changing or removing the image at all.
            Arr::forget($data, 'image');
        }

        $artist->update($data);

        return $artist->refresh();
    }
}
