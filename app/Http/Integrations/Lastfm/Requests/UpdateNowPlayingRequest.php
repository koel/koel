<?php

namespace App\Http\Integrations\Lastfm\Requests;

use App\Http\Integrations\Lastfm\Contracts\RequiresSignature;
use App\Models\Album;
use App\Models\Song;
use App\Models\User;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

final class UpdateNowPlayingRequest extends Request implements HasBody, RequiresSignature
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(private readonly Song $song, private readonly User $user)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/';
    }

    /** @return array<mixed> */
    protected function defaultBody(): array
    {
        $parameters = [
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
