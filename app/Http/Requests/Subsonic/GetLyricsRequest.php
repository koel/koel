<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

/**
 * @property ?string $artist
 * @property ?string $title
 */
class GetLyricsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'artist' => [Rule::nullable(), Rule::string()],
            'title' => [Rule::nullable(), Rule::string()],
        ];
    }
}
