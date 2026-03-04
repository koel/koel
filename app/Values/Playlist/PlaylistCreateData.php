<?php

namespace App\Values\Playlist;

use App\Exceptions\PlaylistBothSongsAndRulesProvidedException;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Contracts\Support\Arrayable;

final readonly class PlaylistCreateData implements Arrayable
{
    /**
     * @param array<string> $playableIds
     */
    private function __construct(
        public string $name,
        public string $description,
        public ?string $folderId,
        public ?string $cover,
        public array $playableIds,
        public ?SmartPlaylistRuleGroupCollection $ruleGroups,
    ) {
        throw_if($this->ruleGroups && $this->playableIds, PlaylistBothSongsAndRulesProvidedException::class);
    }

    public static function make(
        string $name,
        string $description = '',
        ?string $folderId = null,
        ?string $cover = null,
        array $playableIds = [],
        ?SmartPlaylistRuleGroupCollection $ruleGroups = null,
    ): self {
        return new self(
            name: $name,
            description: $description,
            folderId: $folderId,
            cover: $cover,
            playableIds: $playableIds,
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
            'playable_ids' => $this->playableIds,
            'rule_groups' => $this->ruleGroups,
        ];
    }
}
