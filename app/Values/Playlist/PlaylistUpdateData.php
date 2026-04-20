<?php

namespace App\Values\Playlist;

use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Contracts\Support\Arrayable;

final readonly class PlaylistUpdateData implements Arrayable
{
    private function __construct(
        public string $name,
        public string $description,
        public ?string $folderId,
        public ?string $folderName,
        public ?string $cover,
        public ?SmartPlaylistRuleGroupCollection $ruleGroups,
        public ?bool $isLocked = null,
    ) {}

    public static function make(
        string $name,
        string $description = '',
        ?string $folderId = null,
        ?string $folderName = null,
        ?string $cover = null,
        ?SmartPlaylistRuleGroupCollection $ruleGroups = null,
        ?bool $isLocked = null,
    ): self {
        return new self(
            name: $name,
            description: $description,
            folderId: $folderId,
            folderName: $folderName,
            cover: $cover,
            ruleGroups: $ruleGroups,
            isLocked: $isLocked,
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
            'is_locked' => $this->isLocked,
        ];
    }
}
