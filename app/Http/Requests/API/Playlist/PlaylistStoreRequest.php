<?php

namespace App\Http\Requests\API\Playlist;

use App\Http\Requests\API\Request;
use App\Models\PlaylistFolder;
use App\Rules\AllPlayablesAreAccessibleBy;
use App\Rules\ValidImageData;
use App\Rules\ValidSmartPlaylistRulePayload;
use Illuminate\Validation\Rule;

/**
 * @property array<string> $songs
 * @property-read ?string $folder_id
 * @property-read ?string $description
 * @property-read array $rules
 * @property-read string $name
 * @property-read ?string $cover
 */
class PlaylistStoreRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'songs' => ['array', new AllPlayablesAreAccessibleBy($this->user())],
            'description' => 'string|sometimes', // backward compatibility for mobile apps
            'rules' => ['array', 'nullable', new ValidSmartPlaylistRulePayload()],
            'folder_id' => ['nullable', 'sometimes', Rule::exists(PlaylistFolder::class, 'id')],
            'cover' => ['sometimes', 'nullable', new ValidImageData()],
        ];
    }
}
