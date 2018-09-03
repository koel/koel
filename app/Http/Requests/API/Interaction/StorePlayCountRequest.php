<?php

namespace App\Http\Requests\API\Interaction;

use App\Http\Requests\API\Request;

/**
 * @property string $song The song's ID
 */
class StorePlayCountRequest extends Request
{
    public function rules(): array
    {
        return [
            'song' => 'required',
        ];
    }
}
