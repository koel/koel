<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\ProfileUpdateRequest;
use Illuminate\Contracts\Hashing\Hasher as Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * @group 7. User management
 */
class ProfileController extends Controller
{
    private $hash;

    public function __construct(Hash $hash)
    {
        $this->hash = $hash;
    }

    /**
     * Get current user's profile.
     *
     * @response {
     *   "id": 42,
     *   "name": "John Doe",
     *   "email": "john@doe.com"
     * }
     *
     * @return JsonResponse
     */
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update current user's profile.
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
    public function update(ProfileUpdateRequest $request)
    {
        $data = $request->only('name', 'email');

        if ($request->password) {
            $data['password'] = $this->hash->make($request->password);
        }

        return response()->json($request->user()->update($data));
    }
}
