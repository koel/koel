<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use App\Http\Resources\PlaylistResource;

class SmartPlaylistResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return $result->action === 'create_smart_playlist';
    }

    public static function serialize(AiAssistantResult $result): array
    {
        return [
            'playlist' => PlaylistResource::make($result->data['playlist']),
        ];
    }
}
