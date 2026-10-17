<?php

namespace App\Http\Requests\API\Playlist;

use App\Http\Requests\API\Concerns\ValidatesOptionalImage;
use App\Http\Requests\API\Playlist\Concerns\ValidatesPlaylistFolder;
use App\Http\Requests\API\Request;
use App\Rules\ValidSmartPlaylistRulePayload;
use App\Values\Playlist\PlaylistUpdateData;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read ?string $description
 * @property-read ?string $folder_id
 * @property-read ?string $folder_name
 * @property-read array $rules
 * @property-read ?string $cover
 */
class PlaylistUpdateRequest extends Request
{
    use ValidatesPlaylistFolder;
    use ValidatesOptionalImage;

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => [Rule::required()],
            'description' => [Rule::string(), Rule::sometimes(), Rule::nullable()],
            'rules' => [Rule::array(), Rule::nullable(), new ValidSmartPlaylistRulePayload()],
            ...$this->playlistFolderRules(),
            'cover' => self::optionalImageRules(),
        ];
    }

    public function toDto(): PlaylistUpdateData
    {
        return PlaylistUpdateData::make(
            name: $this->name,
            description: (string) $this->description,
            folderId: $this->folder_id,
            folderName: $this->folder_name,
            cover: $this->optionalImageInput('cover'),
            ruleGroups: $this->rules ? SmartPlaylistRuleGroupCollection::create(Arr::wrap($this->rules)) : null,
        );
    }
}
