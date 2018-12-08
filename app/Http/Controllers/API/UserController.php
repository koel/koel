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
     *
     * @response {
     *   "id": 42,
     *   "name": "John Doe",
     *   "email": "john@doe.com"
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
        ]));
    }

    /**
     * Update a user
     *
     * @bodyParam name string required New name. Example: Johny Doe
     * @bodyParam email string required New email. Example: johny@doe.com
     * @bodyParam password string New password (null/blank for no change)
     *
     * @response []
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
