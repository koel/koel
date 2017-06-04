<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\ProfileUpdateRequest;
use Hash;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ProfileController extends Controller
{
    /**
     * Update the current user's profile.
     *
     * @param ProfileUpdateRequest $request
     *
     * @throws RuntimeException
     *
     * @return JsonResponse
     */
    public function update(ProfileUpdateRequest $request)
    {
        $data = $request->only('name', 'email');

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        return response()->json($request->user()->update($data));
    }
}
