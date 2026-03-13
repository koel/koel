<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use App\Http\Resources\SongResource;

class SuggestSongsResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return $result->action === 'suggest_songs';
    }

    public static function serialize(AiAssistantResult $result): array
    {
        return [
            'songs' => SongResource::collection($result->data['songs']),
            'list' => $result->data['list'] ?? '',
        ];
    }
}
