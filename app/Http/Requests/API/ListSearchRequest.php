<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property-read string $q
 */
class ListSearchRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'q' => [Rule::required(), Rule::string()],
            'favorites_only' => [Rule::sometimes(), Rule::boolean()],
        ];
    }
}
