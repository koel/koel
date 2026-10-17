<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property-read string $q
 */
class SearchRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return ['q' => [Rule::required()]];
    }
}
