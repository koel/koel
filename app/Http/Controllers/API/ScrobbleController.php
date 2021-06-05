<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\ScrobbleStoreRequest;
use App\Jobs\ScrobbleJob;
use App\Models\Song;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class ScrobbleController extends Controller
{
    /** @var User */
    private ?Authenticatable $currentUser;

    public function __construct(?Authenticatable $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    public function store(ScrobbleStoreRequest $request, Song $song)
    {
        if (!$song->artist->is_unknown && $this->currentUser->connectedToLastfm()) {
            ScrobbleJob::dispatch($this->currentUser, $song, $request->timestamp);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
