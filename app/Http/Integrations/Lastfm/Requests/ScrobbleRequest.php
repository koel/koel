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

final class ScrobbleRequest extends Request implements HasBody, RequiresSignature
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly Song $song,
        private readonly User $user,
        private readonly int $timestamp
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/';
    }

    /** @return array<mixed> */
    protected function defaultBody(): array
    {
        $body = [
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
