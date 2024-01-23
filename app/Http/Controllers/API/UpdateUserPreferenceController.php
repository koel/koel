<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UpdateUserPreferencesRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\Auth\Authenticatable;

class UpdateUserPreferenceController extends Controller
{
    /** @param User $user */
    public function __invoke(UpdateUserPreferencesRequest $request, UserService $service, Authenticatable $user)
    {
        $service->savePreference($user, $request->key, $request->value);

        return response()->noContent();
    }
}
