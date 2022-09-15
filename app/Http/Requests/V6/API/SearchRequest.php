<?php

namespace App\Http\Requests\V6\API;

use App\Http\Requests\API\Request;

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
