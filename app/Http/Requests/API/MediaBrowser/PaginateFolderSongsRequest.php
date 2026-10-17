<?php

namespace App\Http\Requests\API\MediaBrowser;

use App\Http\Requests\API\Request;
use Illuminate\Validation\Rule;

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
            'cursor' => [Rule::sometimes(), Rule::nullable(), Rule::string()],
            'folder' => [Rule::sometimes(), Rule::nullable(), Rule::string()],
        ];
    }
}
