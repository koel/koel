<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

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
            'type' => [Rule::required(), Rule::string(), Rule::in(self::SUPPORTED_TYPES)],
            'size' => [Rule::numeric()->integer()->min(1)->max(500)],
            'offset' => [Rule::numeric()->integer()->min(0)],
            'fromYear' => ['required_if:type,byYear', Rule::numeric()->integer()],
            'toYear' => ['required_if:type,byYear', Rule::numeric()->integer()],
            'genre' => ['required_if:type,byGenre', Rule::string()],
        ];
    }
}
