<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;

class AiResultSerializerRegistry
{
    /** @var array<class-string<Contracts\AiResultSerializer>> */
    private static array $serializers = [
        PlaySongsResultSerializer::class,
        SuggestSongsResultSerializer::class,
        FavoriteResultSerializer::class,
        PlaylistSongsResultSerializer::class,
        SmartPlaylistResultSerializer::class,
        RadioStationResultSerializer::class,
        UpdateAlbumResultSerializer::class,
        UpdateArtistResultSerializer::class,
        ShowLyricsResultSerializer::class,
        UpdateLyricsResultSerializer::class,
    ];

    public static function serialize(AiAssistantResult $result): array
    {
        foreach (self::$serializers as $serializer) {
            if ($serializer::supports($result)) {
                return $serializer::serialize($result);
            }
        }

        return [];
    }
}
