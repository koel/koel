<?php

namespace App\Http\Requests\API\PlaylistFolder;

use App\Http\Requests\API\Request;

/**
 * @property-read string $name
 */
class PlaylistFolderStoreRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
        ];
    }
}
