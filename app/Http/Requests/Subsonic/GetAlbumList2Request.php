<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property string $type
 */
class GetAlbumList2Request extends Request
{
    public const array SUPPORTED_TYPES = [
        'newest',
        'frequent',
        'random',
        'starred',
        'alphabeticalByName',
    ];

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:' . implode(',', self::SUPPORTED_TYPES)],
            'size' => ['integer', 'min:1', 'max:500'],
            'offset' => ['integer', 'min:0'],
        ];
    }
}
