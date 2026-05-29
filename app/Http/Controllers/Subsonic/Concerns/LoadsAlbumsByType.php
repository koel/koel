<?php

namespace App\Http\Controllers\Subsonic\Concerns;

use App\Exceptions\Subsonic\UnsupportedAlbumListTypeException;
use App\Http\Requests\Subsonic\GetAlbumList2Request;
use App\Models\Album;
use App\Repositories\AlbumRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Dispatches the Subsonic `type` parameter onto AlbumRepository methods.
 * Shared by `GetAlbumListController` (v1) and `GetAlbumList2Controller` (v2)
 * because both endpoints accept the same parameters; only the response wrapper
 * and album-child shape differ.
 */
trait LoadsAlbumsByType
{
    /** @return Collection<int, Album> */
    private function loadAlbumsByType(
        AlbumRepository $albumRepository,
        GetAlbumList2Request $request,
        int $size,
        int $offset,
    ): Collection {
        return match ($request->type) {
            'newest' => $albumRepository->getRecentlyAdded($size),
            'frequent' => $albumRepository->getMostPlayed($size),
            'random' => $albumRepository->getRandom($size),
            'starred' => $albumRepository->getFavorites($size, $offset),
            'recent' => $albumRepository->getRecentlyPlayed($size),
            'highest' => $albumRepository->getHighestRated($size, $offset),
            'byYear' => $albumRepository->getByYearRange(
                $request->integer('fromYear'),
                $request->integer('toYear'),
                $size,
                $offset,
            ),
            'byGenre' => $albumRepository->getByGenre((string) $request->input('genre'), $size, $offset),
            'alphabeticalByName' => $albumRepository->getOrdered('albums.name', 'asc', $size, $offset),
            'alphabeticalByArtist' => $albumRepository->getOrdered('albums.artist_name', 'asc', $size, $offset),
            default => throw UnsupportedAlbumListTypeException::create($request->type),
        };
    }
}
