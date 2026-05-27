<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class GetLicenseController extends Controller
{
    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        return SubsonicResponse::ok([
            'license' => [
                'valid' => true,
                'email' => $user->email,
            ],
        ]);
    }
}
