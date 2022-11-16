<?php

namespace App\Values;

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
final class CompositionToken
{
    private function __construct(public string $apiToken, public string $audioToken)
    {
    }

    public static function fromAccessTokens(NewAccessToken $api, NewAccessToken $audio): self
    {
        return new self($api->plainTextToken, $audio->plainTextToken);
    }
}
