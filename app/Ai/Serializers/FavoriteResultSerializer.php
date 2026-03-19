<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use App\Enums\FavoriteableType;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\PodcastResource;
use App\Http\Resources\RadioStationResource;
use App\Http\Resources\SongResource;

class FavoriteResultSerializer implements AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool
    {
        return in_array($result->action, ['add_to_favorites', 'remove_from_favorites'], true);
    }

    public static function serialize(AiAssistantResult $result): array
    {
        /** @var FavoriteableType $type */
        $type = $result->data['type'];
        $entities = $result->data['entities'];

        $serialized = match ($type) {
            FavoriteableType::ALBUM => ['albums' => AlbumResource::collection($entities)],
            FavoriteableType::ARTIST => ['artists' => ArtistResource::collection($entities)],
            FavoriteableType::RADIO_STATION => ['stations' => RadioStationResource::collection($entities)],
            FavoriteableType::PODCAST => ['podcasts' => PodcastResource::collection($entities)],
            default => ['songs' => SongResource::collection($entities)],
        };

        return ['type' => $type->value, ...$serialized];
    }
}
