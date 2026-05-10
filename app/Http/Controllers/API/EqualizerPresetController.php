<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreEqualizerPresetRequest;
use App\Models\User;
use App\Services\EqualizerPresetService;
use App\Values\EqualizerPreset;
use Illuminate\Contracts\Auth\Authenticatable;

class EqualizerPresetController extends Controller
{
    public function __construct(
        private readonly EqualizerPresetService $service,
    ) {}

    /** @param User $user */
    public function store(StoreEqualizerPresetRequest $request, Authenticatable $user)
    {
        $draft = EqualizerPreset::make(name: $request->name, preamp: (float) $request->preamp, gains: $request->gains);

        return response()->json($this->service->addPresetForUser($user, $draft));
    }

    /** @param User $user */
    public function destroy(string $id, Authenticatable $user)
    {
        $this->service->removePresetForUser($user, $id);

        return response()->noContent();
    }
}
