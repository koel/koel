<?php

namespace App\Http\Requests\API;

use App\Models\Song;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * @property-read string $song
 * @property-read int $position
 */
class UpdatePlaybackStatusRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'song' => [Rule::required(), Rule::string(), new Exists(Song::class, 'id')],
            'position' => [Rule::required(), Rule::numeric()->integer()],
        ];
    }
}
