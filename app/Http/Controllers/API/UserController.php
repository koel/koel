<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\UserStoreRequest;
use App\Http\Requests\API\UserUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher as Hash;
use Illuminate\Http\Response;

class UserController extends Controller
{
    private Hash $hash;

    public function __construct(Hash $hash)
    {
        $this->hash = $hash;
    }

    public function store(UserStoreRequest $request)
    {
        return response()->json(User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $this->hash->make($request->password),
            'is_admin' => $request->is_admin,
        ]));
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->only('name', 'email', 'is_admin');

        if ($request->password) {
            $data['password'] = $this->hash->make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);

        $user->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
