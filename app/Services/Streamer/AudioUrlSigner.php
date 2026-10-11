<?php

namespace App\Services\Streamer;

use App\Models\RadioStation;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class AudioUrlSigner
{
    private const int VALID_MINUTES = 30;

    public function signSongPlayUrl(Song $song, User $user, bool $transcode): string
    {
        $parameters = ['song' => $song->id, 'user' => $user->public_id];

        if ($transcode) {
            $parameters['transcode'] = 1;
        }

        return static_url(self::signRoute('song.play', $parameters));
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

    private static function signRoute(string $name, array $parameters): string
    {
        return URL::temporarySignedRoute($name, now()->addMinutes(self::VALID_MINUTES), $parameters, absolute: false);
    }
}
