<?php

namespace App\Http\Requests\API\Playlist;

use App\Http\Requests\API\Request;
use App\Models\PlaylistFolder;
use App\Rules\ValidImageData;
use App\Rules\ValidSmartPlaylistRulePayload;
use App\Values\Playlist\PlaylistUpdateData;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read ?string $description
 * @property-read ?string $folder_id
 * @property-read array $rules
 * @property-read ?string $cover
 */
class PlaylistUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'description' => 'string|sometimes',
            'rules' => ['array', 'nullable', new ValidSmartPlaylistRulePayload()],
            'folder_id' => ['nullable', 'sometimes', Rule::exists(PlaylistFolder::class, 'id')],
            'cover' => ['string', 'sometimes', 'nullable', new ValidImageData()],
        ];
    }

    public function toDto(): PlaylistUpdateData
    {
        return PlaylistUpdateData::make(
            name: $this->name,
            description: (string) $this->description,
            folderId: $this->folder_id,
            cover: $this->cover,
            ruleGroups: $this->rules ? SmartPlaylistRuleGroupCollection::create(Arr::wrap($this->rules)) : null,
        );
    }
}
