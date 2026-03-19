<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\SongResource;

class PlaylistSongsResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return in_array($result->action, ['add_to_playlist', 'remove_from_playlist'], true);
    }

    public static function serialize(AiAssistantResult $result): array
    {
        return [
            'songs' => SongResource::collection($result->data['songs']),
            'playlist' => PlaylistResource::make($result->data['playlist']),
        ];
    }
}
