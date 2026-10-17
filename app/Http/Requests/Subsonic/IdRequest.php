<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

/**
 * @property string $id
 */
class IdRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id' => [Rule::required(), Rule::string()],
        ];
    }
}
