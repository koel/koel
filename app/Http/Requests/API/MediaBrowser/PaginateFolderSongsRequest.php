<?php

namespace App\Http\Requests\API\MediaBrowser;

use App\Http\Requests\API\Request;

/**
 * @property-read ?string $cursor
 * @property-read ?string $folder
 */
class PaginateFolderSongsRequest extends Request
{
    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'cursor' => ['sometimes', 'nullable', 'string'],
            'folder' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
