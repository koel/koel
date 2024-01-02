<?php

namespace App\Http\Requests\API;

use App\Repositories\SongRepository;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $sort
 * @property-read string $order
 * @property-read int $limit
 */
class FetchSongsForQueueRequest extends Request
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
