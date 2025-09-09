<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $q
 */
class SearchRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return ['q' => 'required'];
    }
}
