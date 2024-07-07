<?php

namespace App\Casts\Podcast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use PhanAn\Poddle\Values\ChannelMetadata;
use Throwable;

class PodcastMetadataCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ChannelMetadata
    {
        try {
            return ChannelMetadata::fromArray(json_decode($value, true));
        } catch (Throwable) {
            return ChannelMetadata::fromArray([]);
        }
    }

    /** @param ChannelMetadata|array<mixed>|null $value */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (is_array($value)) {
            $value = ChannelMetadata::fromArray($value);
        }

        return $value?->toJson() ?? json_encode([]);
    }
}
