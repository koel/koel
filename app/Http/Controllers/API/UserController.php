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

        try {
            return UserResource::make($this->userService->updateUser(
                $user,
                $request->name,
                $request->email,
                $request->password,
                $request->get('is_admin') ?: false
            ));
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
