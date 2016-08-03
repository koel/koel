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
     * @return \Illuminate\Http\JsonResponse
     * @throws \RuntimeException
     */
    public function update(ProfileUpdateRequest $request)
    {
        $data = $request->only('name', 'email');

        if ($password = $request->input('password')) {
            $data['password'] = Hash::make($password);
        }

        return response()->json(auth()->user()->update($data));
    }
}
