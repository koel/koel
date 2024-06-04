<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class SongLyricsCast implements CastsAttributes
{
    /** @param string|null $value */
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (!$value) {
            return '';
        }

        // Since we're displaying the lyrics using <pre>, replace breaks with newlines and strip all tags.
        $value = strip_tags(preg_replace('#<br\s*/?>#i', PHP_EOL, $value));

        // also remove the timestamps that often come with LRC files
        return preg_replace('/\[\d{2}:\d{2}.\d{2}]\s*/m', '', $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
