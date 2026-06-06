<?php

namespace App\Http\Controllers;

use App\Models\RadioStation;
use App\Models\User;
use App\Services\Radio\RadioStreamService;
use Illuminate\Contracts\Auth\Authenticatable;

class StreamRadioController extends Controller
{
    /**
     * @param User $user
     */
    public function __invoke(Authenticatable $user, RadioStation $radioStation, RadioStreamService $radioStreamService)
    {
        $this->authorize('access', $radioStation);

        return $radioStreamService->stream($radioStation);
    }
}
