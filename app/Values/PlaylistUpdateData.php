<?php

namespace App\Values;

use App\Http\Requests\API\Playlist\PlaylistUpdateRequest;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

readonly class PlaylistUpdateData implements Arrayable
{
    private function __construct(
        public string $name,
        public string $description,
        public ?string $folderId,
        public ?SmartPlaylistRuleGroupCollection $ruleGroups,
    ) {
    }

    public static function fromRequest(PlaylistUpdateRequest $request): self
    {
        return new self(
            name: $request->name,
            description: (string) $request->description,
            folderId: $request->folder_id,
            ruleGroups: $request->rules ? SmartPlaylistRuleGroupCollection::create(Arr::wrap($request->rules)) : null,
        );
    }

    public static function make(
        string $name,
        string $description = '',
        ?string $folderId = null,
        ?SmartPlaylistRuleGroupCollection $ruleGroups = null,
    ): self {
        return new self(
            name: $name,
            description: $description,
            folderId: $folderId,
            ruleGroups: $ruleGroups,
        );
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'folder_id' => $this->folderId,
            'rule_groups' => $this->ruleGroups,
        ];
    }
}
