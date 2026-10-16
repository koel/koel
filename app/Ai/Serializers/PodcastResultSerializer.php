<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use App\Http\Resources\PodcastResource;

class PodcastResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return $result->action === 'add_podcast';
    }

    public static function serialize(AiAssistantResult $result): array
    {
        return [
            'podcast' => PodcastResource::make($result->data['podcast']),
        ];
    }
}
