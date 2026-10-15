<?php

namespace App\Http\Controllers\API\Auth\Passkey;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Auth\Passkey\StorePasskeyRequest;
use App\Http\Resources\PasskeyResource;
use App\Models\Passkey;
use App\Models\User;
use App\Services\Auth\PasskeyService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class PasskeyController extends Controller
{
    public function __construct(
        private readonly PasskeyService $passkeyService,
    ) {}

    /** @param User $user */
    public function index(Authenticatable $user)
    {
        return PasskeyResource::collection($user->passkeys);
    }

    /** @param User $user */
    public function store(StorePasskeyRequest $request, Authenticatable $user)
    {
        $passkey = $this->passkeyService->registerPasskey($user, $request->name, $request->credential());

        return PasskeyResource::make($passkey)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /** @param User $user */
    public function destroy(Passkey $passkey, Authenticatable $user)
    {
        $this->authorize('delete', $passkey);

        $this->passkeyService->deletePasskey($user, $passkey);

        return response()->noContent();
    }
}
