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
use App\Services\LibraryManager;
use App\Services\SongService;
use App\Values\SongUpdateData;
use Illuminate\Contracts\Auth\Authenticatable;

class SongController extends Controller
{
    /** @param User $user */
    public function __construct(
        private SongService $songService,
        private SongRepository $songRepository,
        private AlbumRepository $albumRepository,
        private ArtistRepository $artistRepository,
        private LibraryManager $libraryManager,
        private ?Authenticatable $user
    ) {
    }

    public function index(SongListRequest $request)
    {
        return SongResource::collection(
            $this->songRepository->getForListing(
                $request->sort ?: 'songs.title',
                $request->order ?: 'asc',
                $this->user
            )
        );
    }

    public function show(Song $song)
    {
        return SongResource::make($this->songRepository->getOne($song->id));
    }

    public function update(SongUpdateRequest $request)
    {
        $updatedSongs = $this->songService->updateSongs($request->songs, SongUpdateData::fromRequest($request));
        $albums = $this->albumRepository->getByIds($updatedSongs->pluck('album_id')->toArray());

        $artists = $this->artistRepository->getByIds(
            array_merge(
                $updatedSongs->pluck('artist_id')->all(),
                $updatedSongs->pluck('album_artist_id')->all()
            )
        );

        return response()->json([
            'songs' => SongResource::collection($updatedSongs),
            'albums' => AlbumResource::collection($albums),
            'artists' => ArtistResource::collection($artists),
            'removed' => $this->libraryManager->prune(),
        ]);
    }

    public function destroy(DeleteSongsRequest $request)
    {
        $this->authorize('admin', $this->user);

        $this->songService->deleteSongs($request->songs);

        return response()->noContent();
    }
}
