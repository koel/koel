<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property string $name
 * @property list<string> $songId
 */
class CreatePlaylistRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $this->merge(['songId' => Arr::wrap($this->input('songId'))]);
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => [Rule::required(), Rule::string()],
        ];
    }
}
