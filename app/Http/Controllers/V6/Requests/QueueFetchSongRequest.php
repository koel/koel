<?php

namespace App\Http\Controllers\V6\Requests;

use App\Http\Requests\API\Request;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $sort
 * @property-read string $order
 * @property-read int $limit
 */
class QueueFetchSongRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'order' => ['required', Rule::in('asc', 'desc', 'rand')],
            'limit' => 'required|integer|min:1',
            'sort' => 'required_unless:order,rand',
        ];
    }
}
