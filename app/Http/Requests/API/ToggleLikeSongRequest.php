<?php

namespace App\Http\Requests\API;

/** @property-read string $song */
class ToggleLikeSongRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'song' => 'required|exists:songs,id',
        ];
    }
}
