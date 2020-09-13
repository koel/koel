<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher as Hash;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    private $hash;

    /** @var User */
    private $currentUser;

    public function __construct(Hash $hash, ?Authenticatable $currentUser)
    {
        $this->hash = $hash;
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

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
