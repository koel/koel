<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\DeleteSongsRequest;
use App\Http\Requests\API\SongListRequest;
use App\Http\Requests\API\SongUpdateRequest;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\SongResource;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use App\Services\SongService;
use Illuminate\Contracts\Auth\Authenticatable;

class SongController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly SongService $songService,
        private readonly SongRepository $songRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly Authenticatable $user,
    ) {
    }

    public function index(SongListRequest $request)
    {
        return SongResource::collection(
            $this->songRepository->paginate(
                sortColumns: $request->sort ? explode(',', $request->sort) : ['songs.title'],
                sortDirection: $request->order ?: 'asc',
                scopedUser: $this->user
            )
        );
    }

    public function show(Song $song)
    {
        $this->authorize('access', $song);

        return SongResource::make($this->songRepository->getOne($song->id, $this->user));
    }

    public function update(SongUpdateRequest $request)
    {
        // Don't use SongRepository::findMany() because it'd be already catered to the current user.
        Song::query()->findMany($request->songs)->each(fn (Song $song) => $this->authorize('edit', $song));

        $result = $this->songService->updateSongs($request->songs, $request->toDto());
        $albums = $this->albumRepository->getMany($result->updatedSongs->pluck('album_id')->toArray());

        $artists = $this->artistRepository->getMany(
            array_merge(
                $result->updatedSongs->pluck('artist_id')->all(),
                $result->updatedSongs->pluck('album_artist_id')->all()
            )
        );

        return response()->json([
            'songs' => SongResource::collection($result->updatedSongs),
            'albums' => AlbumResource::collection($albums),
            'artists' => ArtistResource::collection($artists),
            'removed' => [
                'artist_ids' => $result->removedArtistIds->toArray(),
                'album_ids' => $result->removedAlbumIds->toArray(),
            ],
        ]);
    }

    public function destroy(DeleteSongsRequest $request)
    {
        // Don't use SongRepository::findMany() because it'd be already catered to the current user.
        Song::query()->findMany($request->songs)->each(fn (Song $song) => $this->authorize('delete', $song));

        $this->songService->deleteSongs($request->songs);

        return response()->noContent();
    }
}
