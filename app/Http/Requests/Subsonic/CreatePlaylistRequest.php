<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property string $name
 * @property list<string> $songId
 */
class CreatePlaylistRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $this->merge(['songId' => (array) $this->input('songId', [])]);
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }
}
