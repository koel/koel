<?php

namespace App\Http\Requests\API;

use App\Models\Song;
use Illuminate\Validation\Rule;

/**
 * @property-read string $song
 * @property-read int $position
 */
class UpdatePlaybackStatusRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'song' => [Rule::exists(Song::class, 'id')],
            'position' => 'required|integer',
        ];
    }
}
