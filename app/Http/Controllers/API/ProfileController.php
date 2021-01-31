<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\ProfileUpdateRequest;
use App\Models\User;
use App\Services\TokenManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher as Hash;

class ProfileController extends Controller
{
    private $hash;
    private $tokenManager;

    /** @var User */
    private $currentUser;

    public function __construct(Hash $hash, TokenManager $tokenManager, ?Authenticatable $currentUser)
    {
        $this->hash = $hash;
        $this->tokenManager = $tokenManager;
        $this->currentUser = $currentUser;
    }

    public function show()
    {
        return response()->json($this->currentUser);
    }

    public function update(ProfileUpdateRequest $request)
    {
        if (config('koel.misc.demo')) {
            return response()->json();
        }

        $data = $request->only('name', 'email');

        if ($request->password) {
            $data['password'] = $this->hash->make($request->password);
        }

        $this->currentUser->update($data);

        $responseData = $request->password
            ? ['token' => $this->tokenManager->refreshToken($this->currentUser)->plainTextToken]
            : [];

        return response()->json($responseData);
    }
}
