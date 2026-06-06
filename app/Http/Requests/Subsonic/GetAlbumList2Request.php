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
        'recent',
        'highest',
        'byYear',
        'byGenre',
        'alphabeticalByName',
        'alphabeticalByArtist',
    ];

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:' . implode(',', self::SUPPORTED_TYPES)],
            'size' => ['integer', 'min:1', 'max:500'],
            'offset' => ['integer', 'min:0'],
            'fromYear' => ['required_if:type,byYear', 'integer'],
            'toYear' => ['required_if:type,byYear', 'integer'],
            'genre' => ['required_if:type,byGenre', 'string'],
        ];
    }
}
