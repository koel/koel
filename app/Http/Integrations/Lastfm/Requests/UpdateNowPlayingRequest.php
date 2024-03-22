<?php

namespace App\Http\Integrations\Lastfm\Requests;

use App\Models\Album;
use App\Models\Song;
use App\Models\User;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasFormBody;

final class UpdateNowPlayingRequest extends SignedRequest implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(private Song $song, private User $user)
    {
        parent::__construct();
    }

    public function resolveEndpoint(): string
    {
        return '/';
    }

    /** @return array<mixed> */
    protected function defaultBody(): array
    {
        $parameters = [
            'api_key' => config('koel.lastfm.key'),
            'method' => 'track.updateNowPlaying',
            'artist' => $this->song->artist->name,
            'track' => $this->song->title,
            'duration' => $this->song->length,
            'sk' => $this->user->preferences->lastFmSessionKey,
        ];

        if ($this->song->album->name !== Album::UNKNOWN_NAME) {
            $parameters['album'] = $this->song->album->name;
        }

        return $parameters;
    }
}
