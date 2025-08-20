<?php

namespace App\Http\Controllers;

use App\Models\RadioStation;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class StreamRadioController extends Controller
{
    /**
     * @param User $user
     */
    public function __invoke(Authenticatable $user, RadioStation $radioStation)
    {
        $this->authorize('access', $radioStation);

        return redirect($radioStation->url);
    }
}
