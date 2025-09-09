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

    public function updateArtist(Artist $artist, ArtistUpdateData $data): Artist
    {
        Assert::false($artist->is_various, '"Various" artists cannot be updated.');

        // Ensure that the artist name is unique (per user)
        $existingArtistWithTheSameName = $this->artistRepository->findOneBy([
            'name' => $data->name,
            'user_id' => $artist->user_id,
        ]);

        throw_if($existingArtistWithTheSameName?->isNot($artist), ArtistNameConflictException::class);

        if ($data->image) {
            $this->imageStorage->storeArtistImage($artist, $data->image);
        }

        $artist->update(Arr::except($data->toArray(), 'image'));

        return $artist->refresh();
    }

    public function removeArtistImage(Artist $artist): void
    {
        $artist->image = '';
        $artist->save(); // will trigger image cleanup in Artist Observer
    }
}
