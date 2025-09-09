<?php

namespace App\Http\Requests\API\Interaction;

use App\Http\Requests\API\Request;
use Illuminate\Validation\Rule;

/**
 * @property-read string $song The song's ID
 */
class IncreasePlayCountRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'song' => ['required', Rule::exists('songs', 'id')],
        ];
    }
}
