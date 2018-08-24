<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\ProfileUpdateRequest;
use Illuminate\Contracts\Hashing\Hasher as Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProfileController extends Controller
{
    private $hash;

    public function __construct(Hash $hash)
    {
        $this->hash = $hash;
    }

    /**
     * Get the current user's profile.
     *
     * @return JsonResponse
     */
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update the current user's profile.
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
