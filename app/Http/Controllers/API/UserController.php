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

/**
 * @group 7. User management
 */
class UserController extends Controller
{
    private $hash;

    public function __construct(Hash $hash)
    {
        $this->hash = $hash;
    }

    /**
     * Create a new user
     *
     * @bodyParam name string required User's name. Example: John Doe
     * @bodyParam email string required User's email. Example: john@doe.com
     * @bodyParam password string required User's password. Example: SoSecureMuchW0w
     * @bodyParam is_admin boolean required Whether the user is an admin
     *
     * @response {
     *   "id": 42,
     *   "name": "John Doe",
     *   "email": "john@doe.com",
     *   "is_admin": true
     * }
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
            'is_admin' => $request->is_admin,
        ]));
    }

    /**
     * Update a user
     *
     * @bodyParam name string required New name. Example: Johny Doe
     * @bodyParam email string required New email. Example: johny@doe.com
     * @bodyParam password string New password (null/blank for no change)
     * @bodyParam is_admin boolean Whether the user is an admin
     *
     * @response {
     *   "id": 42,
     *   "name": "John Doe",
     *   "email": "john@doe.com",
     *   "is_admin": true
     * }
     *
     * @throws RuntimeException
     *
     * @return JsonResponse
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->only('name', 'email', 'is_admin');

        if ($request->password) {
            $data['password'] = $this->hash->make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    /**
     * Delete a user
     *
     * @response []
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
