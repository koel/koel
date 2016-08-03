<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\UserStoreRequest;
use App\Http\Requests\API\UserUpdateRequest;
use App\Models\User;
use Hash;

class UserController extends Controller
{
    /**
     * Create a new user.
     *
     * @param UserStoreRequest $request
     *
     * @throws \RuntimeException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UserStoreRequest $request)
    {
        return response()->json(User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]));
    }

    /**
     * Update a user.
     *
     * @param UserUpdateRequest $request
     * @param User              $user
     *
     * @throws \RuntimeException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->only('name', 'email');

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        return response()->json($user->update($data));
    }

    /**
     * Delete a user.
     *
     * @param User $user
     *
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        $this->authorize($user);

        return response()->json($user->delete());
    }
}
