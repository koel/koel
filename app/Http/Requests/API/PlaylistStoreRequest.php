<?php

namespace App\Http\Requests\API;

use App\Models\PlaylistFolder;
use App\Models\Song;
use App\Rules\ValidSmartPlaylistRulePayload;
use Illuminate\Validation\Rule;

/**
 * @property array<string> $songs
 * @property-read string $name
 * @property-read int|null $folder_id
 * @property-read array $rules
 */
class PlaylistStoreRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'songs' => 'array',
            'songs.*' => [Rule::exists(Song::class, 'id')],
            'rules' => ['array', 'nullable', new ValidSmartPlaylistRulePayload()],
            'folder_id' => ['nullable', 'sometimes', Rule::exists(PlaylistFolder::class, 'id')],
        ];
    }
}
