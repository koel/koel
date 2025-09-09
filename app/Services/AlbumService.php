<?php

namespace App\Services;

use App\Exceptions\AlbumNameConflictException;
use App\Models\Album;
use App\Repositories\AlbumRepository;
use App\Values\Album\AlbumUpdateData;
use Illuminate\Support\Arr;

class AlbumService
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly ImageStorage $imageStorage,
    ) {
    }

    public function updateAlbum(Album $album, AlbumUpdateData $data): Album
    {
        // Ensure that the album name is unique within the artist
        $existingAlbumWithTheSameName = $this->albumRepository->findOneBy([
            'name' => $data->name,
            'artist_id' => $album->artist_id,
        ]);

        throw_if($existingAlbumWithTheSameName?->isNot($album), AlbumNameConflictException::class);

        if ($data->cover) {
            $this->imageStorage->storeAlbumCover($album, $data->cover);
        }

        $album->update(Arr::except($data->toArray(), 'cover'));

        return $album->refresh();
    }

    public function removeAlbumCover(Album $album): void
    {
        $album->cover = '';
        $album->save(); // will trigger cover/thumbnail cleanup in Album Observer
    }
}
