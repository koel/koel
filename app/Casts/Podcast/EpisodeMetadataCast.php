<?php

namespace App\Casts\Podcast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use PhanAn\Poddle\Values\EpisodeMetadata;
use Throwable;

class EpisodeMetadataCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): EpisodeMetadata
    {
        try {
            return EpisodeMetadata::fromArray(json_decode($value, true));
        } catch (Throwable) {
            return EpisodeMetadata::fromArray([]);
        }
    }

    /** @param EpisodeMetadata|array<mixed>|null $value */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (is_array($value)) {
            $value = EpisodeMetadata::fromArray($value);
        }

        return $value?->toJson() ?? json_encode([]);
    }
}
