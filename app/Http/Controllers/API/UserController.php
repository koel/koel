<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\UserStoreRequest;
use App\Http\Requests\API\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(private UserRepository $userRepository, private UserService $userService)
    {
    }

    public function index()
    {
        $this->authorize('admin', User::class);

        return UserResource::collection($this->userRepository->getAll());
    }

    public function store(UserStoreRequest $request)
    {
        $this->authorize('admin', User::class);

        return UserResource::make($this->userService->createUser(
            $request->name,
            $request->email,
            $request->password,
            $request->get('is_admin') ?: false
        ));
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $this->authorize('admin', User::class);

        return UserResource::make($this->userService->updateUser(
            $user,
            $request->name,
            $request->email,
            $request->password,
            $request->get('is_admin') ?: false
        ));
    }

    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $this->userService->deleteUser($user);

        return response()->noContent();
    }
}
