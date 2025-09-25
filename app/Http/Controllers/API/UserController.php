<?php

namespace App\Http\Controllers\API;

use App\Exceptions\UserProspectUpdateDeniedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\UserStoreRequest;
use App\Http\Requests\API\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserService $userService,
    ) {
    }

    public function index()
    {
        $this->authorize('manage', User::class);

        return UserResource::collection($this->userRepository->getAll());
    }

    public function store(UserStoreRequest $request)
    {
        $this->authorize('manage', User::class);

        return UserResource::make($this->userService->createUser($request->toDto()));
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $this->authorize('update', $user);

        try {
            return UserResource::make($this->userService->updateUser($user, $request->toDto()));
        } catch (UserProspectUpdateDeniedException) {
            abort(Response::HTTP_FORBIDDEN, 'Cannot update a user prospect.');
        }
    }

    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $this->userService->deleteUser($user);

        return response()->noContent();
    }
}
