<?php

namespace App\Services\Streamer;

use App\Models\RadioStation;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class AudioUrlSigner
{
    private const int EXTRA_VALID_SECONDS = 30 * 60;

    public function signSongPlayUrl(Song $song, User $user, bool $transcode): string
    {
        $parameters = ['song' => $song->id, 'user' => $user->public_id];

        if ($transcode) {
            $parameters['transcode'] = 1;
        }

        return static_url(self::signRoute('song.play', $parameters, playbackSeconds: (int) ceil($song->length)));
    }

    public function signRadioStreamUrl(RadioStation $station, User $user): string
    {
        return static_url(self::signRoute('radio.stream', [
            'radioStation' => $station->id,
            'user' => $user->public_id,
        ]));
    }

    public function signSongDownloadUrl(Song $song, User $user): string
    {
        return url(self::signRoute('songs.download', ['songs' => [$song->id], 'user' => $user->public_id]));
    }

    private static function signRoute(string $name, array $parameters, int $playbackSeconds = 0): string
    {
        return URL::temporarySignedRoute(
            $name,
            now()->addSeconds($playbackSeconds + self::EXTRA_VALID_SECONDS),
            $parameters,
            absolute: false,
        );
    }
}
