<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use App\Http\Resources\ArtistResource;

class UpdateArtistResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return $result->action === 'update_artist';
    }

    public static function serialize(AiAssistantResult $result): array
    {
        return [
            'artist' => ArtistResource::make($result->data['artist']),
        ];
    }
}
