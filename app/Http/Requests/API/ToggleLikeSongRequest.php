<?php

namespace App\Http\Requests\API;

/** @property-read string $song */
class ToggleLikeSongRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'song' => 'required|exists:songs,id',
        ];
    }
}
