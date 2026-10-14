<?php

namespace App\Services;

use App\Exceptions\ArtistNameConflictException;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\ArtistRepository;
use App\Services\Image\ImageStorage;
use App\Values\Artist\ArtistUpdateData;
use Illuminate\Database\Eloquent\Collection;
use Webmozart\Assert\Assert;

class ArtistService
{
    public function __construct(
        private readonly RichTextSanitizer $richTextSanitizer,
        private readonly ArtistRepository $artistRepository,
        private readonly ImageStorage $imageStorage,
        private readonly LibrarySummaryService $librarySummaryService,
    ) {}

    public function updateArtist(Artist $artist, ArtistUpdateData $dto): Artist
    {
        Assert::false($artist->is_various, '"Various" artists cannot be updated.');

        // Ensure that the artist name is unique (per user)
        $existingArtistWithTheSameName = $this->artistRepository->findOneBy([
            'name' => $dto->name,
            'user_id' => $artist->user_id,
        ]);

        throw_if($existingArtistWithTheSameName?->isNot($artist), ArtistNameConflictException::class);

        $data = $this->imageStorage->storeOptionalImage($dto->image)->applyToAttributes($dto->toArray(), 'image');

        $data = $this->richTextSanitizer->applySanitizedToAttributes($data, 'description', $dto->description);

        $artist->update($data);

        return $artist->refresh();
    }

    /** @return Collection<int, Artist> */
    public function getAllAlbumArtists(User $user): Collection
    {
        $favoritedOrRatedArtistsById = $this->artistRepository->getFavoritedOrRatedByUser($user)->keyBy('id');

        return $this->librarySummaryService
            ->getAlbumArtistsWithoutFavoritesOrRatings($user)
            ->each(static function (Artist $artist) use ($favoritedOrRatedArtistsById): void {
                $favoritedOrRatedArtist = $favoritedOrRatedArtistsById->get($artist->id);

                $artist->setAttribute('favorited_at', $favoritedOrRatedArtist?->favorited_at);
                $artist->setAttribute('rating', $favoritedOrRatedArtist?->getAttribute('rating'));
            });
    }
}
