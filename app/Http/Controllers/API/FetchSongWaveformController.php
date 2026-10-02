<?php

namespace App\Http\Controllers\API;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Models\Song;
use Illuminate\Http\Response;

#[RequiresPlus]
class FetchSongWaveformController extends Controller
{
    public function __invoke(Song $song)
    {
        $this->authorize('access', $song);

        abort_unless((bool) $song->waveform, Response::HTTP_NOT_FOUND);

        return response()->json(['levels' => $song->waveform->levels]);
    }
}
