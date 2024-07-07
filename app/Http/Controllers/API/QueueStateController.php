<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UpdateQueueStateRequest;
use App\Http\Resources\QueueStateResource;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Contracts\Auth\Authenticatable;

class QueueStateController extends Controller
{
    /** @param User $user */
    public function __construct(private readonly QueueService $queueService, private readonly ?Authenticatable $user)
    {
    }

    public function show()
    {
        return QueueStateResource::make($this->queueService->getQueueState($this->user));
    }

    public function update(UpdateQueueStateRequest $request)
    {
        $this->queueService->updateQueueState($this->user, $request->songs);

        return response()->noContent();
    }
}
