<?php

namespace App\Http\Integrations\Lastfm\Requests;

use App\Models\Album;
use App\Models\Song;
use App\Models\User;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasFormBody;

final class ScrobbleRequest extends SignedRequest implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(private Song $song, private User $user, private int $timestamp)
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
        $body = [
            'api_key' => config('koel.lastfm.key'),
            'method' => 'track.scrobble',
            'artist' => $this->song->artist->name,
            'track' => $this->song->title,
            'timestamp' => $this->timestamp,
            'sk' => $this->user->preferences->lastFmSessionKey,
        ];

        if ($this->song->album->name !== Album::UNKNOWN_NAME) {
            $body['album'] = $this->song->album->name;
        }

        return $body;
    }
}
