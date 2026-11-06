<?php

namespace App\Services;

use App\Exceptions\ArtistNameConflictException;
use App\Facades\License;
use App\Models\Artist;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Services\Image\ImageStorage;
use App\Values\Artist\ArtistUpdateData;
use Illuminate\Support\Facades\DB;
use Webmozart\Assert\Assert;

class ArtistService
{
    public function __construct(
        private readonly RichTextSanitizer $richTextSanitizer,
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ImageStorage $imageStorage,
        private readonly AlbumService $albumService,
        private readonly FavoriteService $favoriteService,
        private readonly RatingService $ratingService,
        private readonly LibrarySummaryService $librarySummaryService,
    ) {}

    public function updateArtist(Artist $artist, ArtistUpdateData $dto): Artist
    {
        Assert::false($artist->is_various, '"Various" artists cannot be updated.');

        // Ensure that the artist name is unique (per user in Plus)
        $existingArtistWithTheSameName = $this->artistRepository->findOneByNameIgnoringCase(
            $dto->name,
            License::isPlus() ? $artist->user_id : null,
            $artist,
        );

        throw_if(
            $existingArtistWithTheSameName !== null,
            ArtistNameConflictException::class,
            $existingArtistWithTheSameName,
        );

        $data = $this->imageStorage->storeOptionalImage($dto->image)->applyToAttributes($dto->toArray(), 'image');

        $data = $this->richTextSanitizer->applySanitizedToAttributes($data, 'description', $dto->description);

        $artist->update($data);

        return $artist->refresh();
    }

    /**
     * Move the source artist's songs, albums, favorites, and ratings to the target artist, then delete the source.
     * An album that shares its name with one of the target's albums is merged into it.
     */
    public function mergeArtists(Artist $source, Artist $target): Artist
    {
        DB::transaction(function () use ($source, $target): void {
            foreach ($source->albums as $album) {
                $namesakeAlbum = $this->albumRepository->findOneByArtistAndNameIgnoringCase($target->id, $album->name);

                if ($namesakeAlbum) {
                    $this->albumService->mergeAlbums($album, $namesakeAlbum);
                }
            }

            $source->albums()->update(['artist_id' => $target->id, 'artist_name' => $target->name]);
            $source->songs()->update(['artist_id' => $target->id, 'artist_name' => $target->name]);
            $this->favoriteService->transferFavorites($source, $target);
            $this->ratingService->transferRatings($source, $target);
            $source->delete();
        });

        $this->librarySummaryService->markLibraryChanged();

        return $target->refresh();
    }
}
