<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Laravel\Sanctum\NewAccessToken;

/**
 * A "composition token" consists of two tokens:
 *
 * - an API token, which has all abilities
 * - an audio token, which has only the "audio" ability i.e. to play and download audio files. This token is used for
 * the audio player on the frontend as part of the GET query string, and thus has limited privileges.
 *
 * This approach helps prevent the API token from being logged by servers and proxies.
 */
final class CompositionToken implements Arrayable
{
    private function __construct(public string $apiToken, public string $audioToken)
    {
    }

    public static function fromAccessTokens(NewAccessToken $api, NewAccessToken $audio): self
    {
        return new self($api->plainTextToken, $audio->plainTextToken);
    }

    public function toArray(): array
    {
        return [
            'token' => $this->apiToken,
            'audio-token' => $this->audioToken,
        ];
    }
}
