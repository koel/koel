<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property-read string $order
 * @property-read string $sort
 * @property-read ?string $cursor
 */
class CursorPaginatedListRequest extends Request
{
    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'sort' => [Rule::sometimes(), Rule::string()],
            'order' => [Rule::sometimes(), Rule::in(['asc', 'desc'])],
            'cursor' => [Rule::sometimes(), Rule::nullable(), Rule::string()],
        ];
    }
}
