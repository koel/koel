<?php

namespace App\Http\Requests\API;

use App\Rules\ValidSmartPlaylistRulePayload;

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
