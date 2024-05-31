<?php

namespace App\Http\Requests\API;

use App\Models\Song;
use Illuminate\Validation\Rule;

/**
 * @property-read array<string> $songs
 * @property-read array<mixed> $data
 */
class SongUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'songs' => ['required', 'array', Rule::exists(Song::class, 'id')->whereNull('podcast_id')],
        ];
    }
}
