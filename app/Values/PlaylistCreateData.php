<?php

namespace App\Values;

use App\Exceptions\PlaylistBothSongsAndRulesProvidedException;
use App\Http\Requests\API\Playlist\PlaylistStoreRequest;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

readonly class PlaylistCreateData implements Arrayable
{
    /**
     * @param array<string> $playableIds
     */
    private function __construct(
        public string $name,
        public string $description,
        public ?string $folderId,
        public array $playableIds,
        public ?SmartPlaylistRuleGroupCollection $ruleGroups,
    ) {
        throw_if($this->ruleGroups && $this->playableIds, PlaylistBothSongsAndRulesProvidedException::class);
    }

    public static function fromRequest(PlaylistStoreRequest $request): self
    {
        return new self(
            name: $request->name,
            description: (string) $request->description,
            folderId: $request->folder_id,
            playableIds: Arr::wrap($request->songs),
            ruleGroups: $request->rules ? SmartPlaylistRuleGroupCollection::create(Arr::wrap($request->rules)) : null,
        );
    }

    public static function make(
        string $name,
        string $description = '',
        ?string $folderId = null,
        array $playableIds = [],
        ?SmartPlaylistRuleGroupCollection $ruleGroups = null,
    ): self {
        return new self(
            name: $name,
            description: $description,
            folderId: $folderId,
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
            'playable_ids' => $this->playableIds,
            'rule_groups' => $this->ruleGroups,
        ];
    }
}
