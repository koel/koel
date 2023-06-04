<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $name
 */
class PlaylistFolderStoreRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
        ];
    }
}
