<?php

namespace App\Http\Requests\API;

use App\Rules\AllPlayablesAreAccessibleBy;

/**
 * @property-read array<string> $songs
 */
class AddSongsToPlaylistRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'songs' => ['required', 'array', new AllPlayablesAreAccessibleBy($this->user())],
        ];
    }
}
