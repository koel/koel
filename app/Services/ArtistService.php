<?php

namespace App\Services;

use App\Enums\ImageChangeType;
use App\Exceptions\ArtistNameConflictException;
use App\Models\Artist;
use App\Repositories\ArtistRepository;
use App\Services\Image\ImageStorage;
use App\Values\Artist\ArtistUpdateData;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class ArtistService
{
    public function __construct(
        private readonly RichTextSanitizer $richTextSanitizer,
        private readonly ArtistRepository $artistRepository,
        private readonly ImageStorage $imageStorage,
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

        $data = $dto->toArray();

        $imageChange = $this->imageStorage->storeOptionalImage($dto->image);

        if ($imageChange->type === ImageChangeType::KEEP) {
            Arr::forget($data, 'image');
        } else {
            $data['image'] = $imageChange->fileName ?? '';
        }

        if ($dto->description === null) {
            Arr::forget($data, 'description');
        } else {
            $data['description'] = $this->richTextSanitizer->sanitize($dto->description);
        }

        $artist->update($data);

        return $artist->refresh();
    }
}
