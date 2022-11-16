<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TokenManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /** @param User $user */
    public function __construct(
        private Hasher $hash,
        private TokenManager $tokenManager,
        private ?Authenticatable $user
    ) {
    }

    public function show()
    {
        return UserResource::make($this->user);
    }

    public function update(ProfileUpdateRequest $request)
    {
        if (config('koel.misc.demo')) {
            return response()->noContent();
        }

        throw_unless(
            $this->hash->check($request->current_password, $this->user->password),
            ValidationException::withMessages(['current_password' => 'Invalid current password'])
        );

        $data = $request->only('name', 'email');

        if ($request->new_password) {
            $data['password'] = $this->hash->make($request->new_password);
        }

        $this->user->update($data);

        $response = UserResource::make($this->user)->response();

        if ($request->new_password) {
            $response->header(
                'Authorization',
                $this->tokenManager->refreshApiToken($request->bearerToken() ?: '')->plainTextToken
            );
        }

        return $response;
    }
}
