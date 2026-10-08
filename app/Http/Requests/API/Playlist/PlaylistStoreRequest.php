<?php

namespace App\Http\Requests\API\Playlist;

use App\Http\Requests\API\Playlist\Concerns\ValidatesPlaylistFolder;
use App\Http\Requests\API\Request;
use App\Rules\AllPlayablesAreAccessibleBy;
use App\Rules\ValidImageData;
use App\Rules\ValidSmartPlaylistRulePayload;
use App\Values\Playlist\PlaylistCreateData;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Support\Arr;

/**
 * @property array<string> $songs
 * @property-read ?string $folder_id
 * @property-read ?string $folder_name
 * @property-read ?string $description
 * @property-read array $rules
 * @property-read string $name
 * @property-read ?string $cover
 */
class PlaylistStoreRequest extends Request
{
    use ValidatesPlaylistFolder;

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'songs' => ['array', new AllPlayablesAreAccessibleBy($this->user())],
            'description' => ['string', 'sometimes', 'nullable'], // backward compatibility for mobile apps
            'rules' => ['array', 'nullable', new ValidSmartPlaylistRulePayload()],
            ...$this->playlistFolderRules(),
            'cover' => ['sometimes', 'nullable', new ValidImageData()],
        ];
    }

    public function toDto(): PlaylistCreateData
    {
        return PlaylistCreateData::make(
            name: $this->name,
            description: (string) $this->description,
            folderId: $this->folder_id,
            folderName: $this->folder_name,
            cover: $this->cover,
            playableIds: Arr::wrap($this->songs),
            ruleGroups: $this->rules ? SmartPlaylistRuleGroupCollection::create(Arr::wrap($this->rules)) : null,
        );
    }
}
