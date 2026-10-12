<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RadioStation;
use App\Models\User;
use App\Services\Streamer\AudioUrlSigner;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchRadioStationStreamUrlController extends Controller
{
    /**
     * @param User $user
     */
    public function __invoke(RadioStation $radioStation, AudioUrlSigner $signer, Authenticatable $user)
    {
        $this->authorize('access', $radioStation);

        return response()->json(['url' => $signer->signRadioStreamUrl($radioStation, $user)]);
    }
}
