<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetUserRequest;
use App\Http\Responses\Subsonic\Resources\UserResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetUserController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /** @param User $user */
    public function __invoke(GetUserRequest $request, Authenticatable $user)
    {
        $isSelf = $user->email === $request->username;

        if (!$isSelf) {
            $this->authorize('manage', User::class);
        }

        $target = $isSelf
            ? $user
            : $this->userRepository->findOneByEmailInOrganization(
                $request->username,
                $user->organization,
            ) ?? throw new ModelNotFoundException();

        return SubsonicResponse::ok(['user' => UserResource::toArray($target)]);
    }
}
