<?php

namespace App\Http\Requests\API;

use App\Rules\AllPlayablesAreAccessibleBy;
use Illuminate\Validation\Rule;

/**
 * @property-read array<string> $songs
 */
class AddSongsToPlaylistRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'songs' => [Rule::required(), Rule::array(), new AllPlayablesAreAccessibleBy($this->user())],
        ];
    }
}
