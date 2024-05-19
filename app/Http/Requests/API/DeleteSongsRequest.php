<?php

namespace App\Http\Requests\API;

use App\Enums\MediaType;
use App\Models\Song;
use Illuminate\Validation\Rule;

/** @property-read array<string> $songs */
class DeleteSongsRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'songs' => ['required', 'array', Rule::exists(Song::class, 'id')->where('type', MediaType::SONG)],
        ];
    }
}
