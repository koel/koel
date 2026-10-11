<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\User;
use App\Services\Streamer\AudioUrlSigner;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchSongDownloadUrlController extends Controller
{
    /**
     * @param User $user
     */
    public function __invoke(Song $song, AudioUrlSigner $signer, Authenticatable $user)
    {
        $this->authorize('download', $song);

        return response()->json(['url' => $signer->signSongDownloadUrl($song, $user)]);
    }
}
