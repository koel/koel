<?php

namespace App\Http\Controllers\V6\Requests;

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
