<?php

namespace App\Http\Requests\API\PlaylistFolder;

use App\Http\Requests\API\Request;
use App\Models\PlaylistFolder;
use Illuminate\Validation\Rule;

/**
 * @property-read ?string $name
 * @property-read ?string $parent_id
 */
class PlaylistFolderUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:191'],
            'parent_id' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists(PlaylistFolder::class, 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
