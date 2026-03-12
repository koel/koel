<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use App\Http\Resources\SongResource;

class UpdateLyricsResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return $result->action === 'update_lyrics';
    }

    public static function serialize(AiAssistantResult $result): array
    {
        return [
            'lyrics' => $result->data['lyrics'] ?? '',
            'song' => isset($result->data['song']) ? SongResource::make($result->data['song']) : null,
        ];
    }
}
