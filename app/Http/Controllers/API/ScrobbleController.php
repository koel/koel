<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ScrobbleStoreRequest;
use App\Jobs\ScrobbleJob;
use App\Models\Song;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class ScrobbleController extends Controller
{
    /** @param User $currentUser */
    public function __construct(private ?Authenticatable $currentUser)
    {
    }

    public function store(ScrobbleStoreRequest $request, Song $song)
    {
        if (!$song->artist->is_unknown && $this->currentUser->connectedToLastfm()) {
            ScrobbleJob::dispatch($this->currentUser, $song, $request->timestamp);
        }

        return response()->noContent();
    }
}
