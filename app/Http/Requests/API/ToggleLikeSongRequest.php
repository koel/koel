<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/** @property-read string $song */
class ToggleLikeSongRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'song' => [Rule::required(), Rule::exists('songs', 'id')],
        ];
    }
}
