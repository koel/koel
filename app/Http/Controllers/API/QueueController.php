<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\QueueFetchSongRequest;
use App\Http\Requests\API\UpdatePlaybackStatusRequest;
use App\Http\Requests\API\UpdateQueueStateRequest;
use App\Http\Resources\QueueStateResource;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Contracts\Auth\Authenticatable;

class QueueController extends Controller
{
    /** @param User $user */
    public function __construct(private QueueService $service, private ?Authenticatable $user)
    {
    }

    public function getState()
    {
        return QueueStateResource::make($this->service->getQueueState($this->user));
    }

    public function updateState(UpdateQueueStateRequest $request)
    {
        $this->service->updateQueueState($this->user, $request->songs);

        return response()->noContent();
    }

    public function updatePlaybackStatus(UpdatePlaybackStatusRequest $request)
    {
        $this->service->updatePlaybackStatus($this->user, $request->song, $request->position);
    }

    public function fetchSongs(QueueFetchSongRequest $request)
    {
        if ($request->order === 'rand') {
            return SongResource::collection($this->service->generateRandomQueueSongs($this->user, $request->limit));
        }

        return SongResource::collection(
            $this->service->generateOrderedQueueSongs(
                $this->user,
                $request->sort,
                $request->order,
                $request->limit
            )
        );
    }
}
