<?php

namespace App\Http\Controllers\API\Me;

use App\Helpers\Uuid;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class RegenerateSubsonicApiKeyController extends Controller
{
    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $user->forceFill(['subsonic_api_key' => Uuid::generate()])->saveQuietly();

        return UserResource::make($user->refresh());
    }
}
