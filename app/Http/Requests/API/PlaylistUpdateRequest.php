<?php

namespace App\Http\Requests\API;

use App\Rules\ValidSmartPlaylistRulePayload;

/**
 * @property-read string $name
 * @property-read array $rules
 */
class PlaylistUpdateRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'rules' => ['array', 'nullable', new ValidSmartPlaylistRulePayload()],
        ];
    }
}
