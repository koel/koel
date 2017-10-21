<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\ProfileUpdateRequest;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProfileController extends Controller
{
    /**
     * Get the current user's profile.
     *
     * @param Request $request
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
