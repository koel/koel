<?php

namespace App\Http\Controllers\API;

use App\Enums\NativelyPlayableLosslessFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\FetchSongPlayUrlRequest;
use App\Models\Song;
use App\Models\User;
use App\Services\Streamer\AudioUrlSigner;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchSongPlayUrlController extends Controller
{
    /**
     * @param User $user
     */
    public function __invoke(
        FetchSongPlayUrlRequest $request,
        Song $song,
        AudioUrlSigner $signer,
        Authenticatable $user,
    ) {
        $this->authorize('access', $song);

        return response()->json([
            'url' => $signer->signSongPlayUrl(
                $song,
                $user,
                $request->boolean('transcode'),
                $request->enums('lossless', NativelyPlayableLosslessFormat::class),
            ),
        ]);
    }
}
