<?php

namespace App\Casts\Podcast;

use App\Values\Podcast\PodcastState;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class PodcastStateCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): PodcastState
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return PodcastState::fromArray($value ?? []);
    }

    /**
     * @param PodcastState|array|null $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_array($value)) {
            $value = PodcastState::fromArray($value);
        }

        return $value?->toJson();
    }
}
