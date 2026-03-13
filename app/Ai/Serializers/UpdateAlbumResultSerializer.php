<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use App\Http\Resources\AlbumResource;

class UpdateAlbumResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return $result->action === 'update_album';
    }

    public static function serialize(AiAssistantResult $result): array
    {
        return [
            'album' => AlbumResource::make($result->data['album']),
        ];
    }
}
