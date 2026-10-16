<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use App\Http\Resources\SongResource;

class PlayEpisodeResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return $result->action === 'play_episode';
    }

    public static function serialize(AiAssistantResult $result): array
    {
        return [
            'episode' => SongResource::make($result->data['episode']),
            'position' => $result->data['position'],
        ];
    }
}
