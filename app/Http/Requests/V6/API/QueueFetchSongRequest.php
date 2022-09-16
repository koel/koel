<?php

namespace App\Http\Requests\V6\API;

use App\Http\Requests\API\Request;
use App\Repositories\SongRepository;
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
            'sort' => [
                'required_unless:order,rand',
                Rule::in(array_keys(SongRepository::SORT_COLUMNS_NORMALIZE_MAP)),
            ],
        ];
    }
}
