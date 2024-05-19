<?php

namespace App\Http\Requests\API;

use App\Enums\MediaType;
use App\Models\Song;
use Illuminate\Validation\Rule;

/**
 * @property-read array<string> $songs
 * @property-read array<mixed> $data
 */
class SongUpdateRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'songs' => ['required', 'array', Rule::exists(Song::class, 'id')->where('type', MediaType::SONG)],
        ];
    }
}
