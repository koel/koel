<?php

namespace App\Http\Requests\API;

use App\Models\PlaylistFolder;
use App\Rules\ValidSmartPlaylistRulePayload;
use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read int|null $folder_id
 * @property-read array $rules
 * @property-read ?bool $own_songs_only
 */
class PlaylistUpdateRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'rules' => ['array', 'nullable', new ValidSmartPlaylistRulePayload()],
            'folder_id' => ['nullable', 'sometimes', Rule::exists(PlaylistFolder::class, 'id')],
            'own_songs_only' => 'sometimes',
        ];
    }
}
