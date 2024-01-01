<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\QueueFetchSongRequest;
use App\Http\Requests\API\UpdatePlaybackStatusRequest;
use App\Http\Requests\API\UpdateQueueStateRequest;
use App\Http\Resources\QueueStateResource;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\QueueService;
use Illuminate\Contracts\Auth\Authenticatable;

class QueueController extends Controller
{
    /** @param User $user */
    public function __construct(
        private SongRepository $songRepository,
        private QueueService $queueService,
        private ?Authenticatable $user
    ) {
    }

    public function getState()
    {
        return QueueStateResource::make($this->queueService->getQueueState($this->user));
    }

    public function updateState(UpdateQueueStateRequest $request)
    {
        $this->queueService->updateQueueState($this->user, $request->songs);

        return response()->noContent();
    }

    public function updatePlaybackStatus(UpdatePlaybackStatusRequest $request)
    {
        $this->queueService->updatePlaybackStatus($this->user, $request->song, $request->position);

        return response()->noContent();
    }

    public function fetchSongs(QueueFetchSongRequest $request)
    {
        if ($request->order === 'rand') {
            return SongResource::collection($this->songRepository->getRandom($request->limit, $this->user));
        }

        return SongResource::collection(
            $this->songRepository->getForQueue(
                $request->sort,
                $request->order,
                $request->limit,
                $this->user
            )
        );
    }
}
