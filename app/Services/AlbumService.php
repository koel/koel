<?php

namespace App\Services;

use App\Exceptions\AlbumNameConflictException;
use App\Models\Album;
use App\Repositories\AlbumRepository;
use App\Values\AlbumUpdateData;
use Webmozart\Assert\Assert;

class AlbumService
{
    public function __construct(private readonly AlbumRepository $albumRepository)
    {
    }

    public function updateAlbum(Album $album, AlbumUpdateData $data): Album
    {
        Assert::false($album->is_unknown);

        $existingAlbumWithTheSameName = $this->albumRepository->findOneBy([
            'name' => $data->name,
            'artist_id' => $album->artist_id,
        ]);

        throw_if($existingAlbumWithTheSameName?->isNot($album), AlbumNameConflictException::class);

        $album->update($data->toArray());

        return $album->refresh();
    }
}
