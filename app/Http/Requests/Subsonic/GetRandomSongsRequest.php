<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $genre
 */
class GetRandomSongsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'size' => [Rule::numeric()->integer()->min(1)->max(500)],
            'genre' => [Rule::sometimes(), Rule::string()],
            'fromYear' => [Rule::sometimes(), Rule::numeric()->integer()],
            'toYear' => [Rule::sometimes(), Rule::numeric()->integer()],
        ];
    }
}
