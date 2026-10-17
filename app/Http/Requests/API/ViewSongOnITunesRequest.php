<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property string $q
 * @property string $api_token
 */
class ViewSongOnITunesRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'q' => [Rule::required()],
            'api_token' => [Rule::required()],
        ];
    }
}
