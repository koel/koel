<?php

namespace App\Casts;

use App\Models\Song;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class SongTitleCast implements CastsAttributes
{
    /**
     * @param Song $model
     * @param string|null $value
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        // If the title is empty, we "guess" the title by extracting the filename from the song's path.
        return $value ?: pathinfo($model->path, PATHINFO_FILENAME);
    }

    /**
     * @param string $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return html_entity_decode($value);
    }
}
