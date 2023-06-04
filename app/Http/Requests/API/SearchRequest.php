<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $q
 */
class SearchRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return ['q' => 'required'];
    }
}
