<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use App\Rules\AllPlayablesAreAccessibleBy;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property string|null $name
 * @property string|null $playlistId
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
            'playlistId' => [Rule::sometimes(), Rule::string()],
            'name' => [Rule::requiredWithout('playlistId'), Rule::string()],
            'songId' => [Rule::array(), new AllPlayablesAreAccessibleBy($this->user())],
        ];
    }
}
