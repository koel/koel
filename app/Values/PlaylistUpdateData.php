<?php

namespace App\Values;

use App\Http\Requests\API\Playlist\PlaylistUpdateRequest;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

final readonly class PlaylistUpdateData implements Arrayable
{
    private function __construct(
        public string $name,
        public string $description,
        public ?string $folderId,
        public ?string $cover,
        public ?SmartPlaylistRuleGroupCollection $ruleGroups,
    ) {
    }

    public static function fromRequest(PlaylistUpdateRequest $request): self
    {
        return new self(
            name: $request->name,
            description: (string) $request->description,
            folderId: $request->folder_id,
            cover: $request->cover,
            ruleGroups: $request->rules ? SmartPlaylistRuleGroupCollection::create(Arr::wrap($request->rules)) : null,
        );
    }

    public static function make(
        string $name,
        string $description = '',
        ?string $folderId = null,
        ?string $cover = null,
        ?SmartPlaylistRuleGroupCollection $ruleGroups = null,
    ): self {
        return new self(
            name: $name,
            description: $description,
            folderId: $folderId,
            cover: $cover,
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
            'cover' => $this->cover,
            'rule_groups' => $this->ruleGroups,
        ];
    }
}
