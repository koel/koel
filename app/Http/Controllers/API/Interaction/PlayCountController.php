<?php

namespace App\Http\Controllers\API\Interaction;

use App\Events\SongStartedPlaying;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Interaction\StorePlayCountRequest;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class PlayCountController extends Controller
{
    /** @param User $user */
    public function __construct(private InteractionService $interactionService, private ?Authenticatable $user)
    {
    }

    public function store(StorePlayCountRequest $request)
    {
        $interaction = $this->interactionService->increasePlayCount($request->song, $this->user);
        event(new SongStartedPlaying($interaction->song, $interaction->user));

        return response()->json($interaction);
    }
}
