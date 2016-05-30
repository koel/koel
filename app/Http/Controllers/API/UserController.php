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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UserStoreRequest $request)
    {
        return response()->json(User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]));
    }

    /**
     * Update a user.
     *
     * @param UserUpdateRequest $request
     * @param User              $user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->only('name', 'email');

        if ($password = $request->input('password')) {
            $data['password'] = Hash::make($password);
        }

        return response()->json($user->update($data));
    }

    /**
     * Delete a user.
     *
     * @param User $user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        $this->authorize($user);

        return response()->json($user->delete());
    }
}
