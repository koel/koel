<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\ProfileUpdateRequest;
use App\Models\User;
use App\Services\TokenManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher as Hash;
use Illuminate\Validation\ValidationException;

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

        throw_unless(
            $this->hash->check($request->current_password, $this->currentUser->password),
            ValidationException::withMessages(['current_password' => 'Invalid current password'])
        );

        $data = $request->only('name', 'email');

        if ($request->new_password) {
            $data['password'] = $this->hash->make($request->new_password);
        }

        $this->currentUser->update($data);

        $responseData = $request->new_password
            ? ['token' => $this->tokenManager->refreshToken($this->currentUser)->plainTextToken]
            : [];

        return response()->json($responseData);
    }
}
