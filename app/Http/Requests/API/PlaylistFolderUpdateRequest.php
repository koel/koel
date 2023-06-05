<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $name
 */
class PlaylistFolderUpdateRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
        ];
    }
}
