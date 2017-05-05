<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\ProfileUpdateRequest;
use App\Models\User;
use Hash;

class ProfileController extends Controller
{
    /**
     * Update the current user's profile.
     *
     * @param ProfileUpdateRequest $request
     *
     * @throws \RuntimeException
     *
     * @return \Illuminate\Http\JsonResponse
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
