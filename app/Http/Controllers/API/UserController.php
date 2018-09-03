<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\UserStoreRequest;
use App\Http\Requests\API\UserUpdateRequest;
use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Hashing\Hasher as Hash;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class UserController extends Controller
{
    private $hash;

    public function __construct(Hash $hash)
    {
        $this->hash = $hash;
    }

    /**
     * Create a new user.
     *
     * @throws RuntimeException
     *
     * @return JsonResponse
     */
    public function store(UserStoreRequest $request)
    {
        return response()->json(User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $this->hash->make($request->password),
        ]));
    }

    /**
     * Update a user.
     *
     * @throws RuntimeException
     *
     * @return JsonResponse
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->only('name', 'email');

        if ($request->password) {
            $data['password'] = $this->hash->make($request->password);
        }

        $user->update($data);

        return response()->json();
    }

    /**
     * Delete a user.
     *
     * @throws Exception
     * @throws AuthorizationException
     *
     * @return JsonResponse
     */
    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);

        $user->delete();

        return response()->json();
    }
}
