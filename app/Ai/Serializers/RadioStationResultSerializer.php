<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use App\Http\Resources\RadioStationResource;

class RadioStationResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return in_array($result->action, ['play_radio_station', 'add_radio_station'], true);
    }

    public static function serialize(AiAssistantResult $result): array
    {
        return [
            'station' => RadioStationResource::make($result->data['station']),
        ];
    }
}
