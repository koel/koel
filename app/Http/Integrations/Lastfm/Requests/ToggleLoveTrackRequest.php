<?php

namespace App\Http\Integrations\Lastfm\Requests;

use App\Models\Song;
use App\Models\User;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasFormBody;

final class ToggleLoveTrackRequest extends SignedRequest implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(private Song $song, private User $user, private bool $love)
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
        return [
            'api_key' => config('koel.lastfm.key'),
            'method' => $this->love ? 'track.love' : 'track.unlove',
            'sk' => $this->user->preferences->lastFmSessionKey,
            'artist' => $this->song->artist->name,
            'track' => $this->song->title,
        ];
    }
}
