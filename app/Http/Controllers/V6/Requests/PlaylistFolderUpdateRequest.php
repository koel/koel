<?php

namespace App\Http\Controllers\V6\Requests;

use App\Http\Requests\API\Request;

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
