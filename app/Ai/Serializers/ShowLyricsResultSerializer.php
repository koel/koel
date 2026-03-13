<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;

class ShowLyricsResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return $result->action === 'show_lyrics';
    }

    public static function serialize(AiAssistantResult $result): array
    {
        return [
            'lyrics' => $result->data['lyrics'],
        ];
    }
}
