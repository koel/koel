<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

/**
 * @property string $genre
 */
class GetSongsByGenreRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'genre' => [Rule::required(), Rule::string()],
            'count' => [Rule::numeric()->integer()->min(1)->max(500)],
            'offset' => [Rule::numeric()->integer()->min(0)],
        ];
    }
}
