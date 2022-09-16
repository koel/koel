<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\V6\API\DeleteSongsRequest;
use App\Services\SongService;
use Illuminate\Contracts\Auth\Authenticatable;

class DeleteSongsController extends Controller
{
    public function __invoke(DeleteSongsRequest $request, SongService $service, Authenticatable $user)
    {
        $this->authorize('admin', $user);

        $service->deleteSongs($request->songs);

        return response()->noContent();
    }
}
