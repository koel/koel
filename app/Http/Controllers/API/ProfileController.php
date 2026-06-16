<?php

namespace App\Http\Controllers\API;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly UserService $userService,
        private readonly Authenticatable $user,
    ) {}

    public function show()
    {
        return UserResource::make($this->user);
    }

    #[DisabledInDemo(Response::HTTP_NO_CONTENT)]
    public function update(ProfileUpdateRequest $request)
    {
        return UserResource::make($this->userService->updateUser($this->user, $request->toDto()));
    }
}
