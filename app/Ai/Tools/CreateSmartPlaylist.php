<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Enums\SmartPlaylistModel;
use App\Enums\SmartPlaylistOperator;
use App\Helpers\Uuid;
use App\Services\PlaylistService;
use App\Values\Playlist\PlaylistCreateData;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class CreateSmartPlaylist implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly PlaylistService $playlistService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Create a smart playlist with auto-updating rules. Use this when the user wants to create a playlist '
            . 'that automatically includes songs matching certain criteria. '
            . 'Available models: title, album.name, artist.name, genre, year, interactions.play_count, '
            . 'interactions.last_played_at, length (in seconds), created_at, updated_at. '
            . 'Available operators for text fields: is, isNot, contains, notContain, beginsWith, endsWith. '
            . 'Available operators for number fields: is, isNot, isGreaterThan, isLessThan, isBetween. '
            . 'Available operators for date fields: is, isNot, inLast (value in days), notInLast (value in days), isBetween. '
            . 'Rules within a group use AND logic. Multiple groups use OR logic. '
            . 'Values are always string arrays: ["value"] for most operators, ["min", "max"] for isBetween.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        $validModels = implode(', ', array_column(SmartPlaylistModel::cases(), 'value'));
        $validOperators = implode(', ', array_column(SmartPlaylistOperator::cases(), 'value'));

        return [
            'name' => $schema->string()->required()->description('The playlist name'),
            'rule_groups' => $schema
                ->array()
                ->items($schema->object([
                    'rules' => $schema
                        ->array()
                        ->items($schema->object([
                            'model' => $schema
                                ->string()
                                ->required()
                                ->description("The field to filter on. Must be one of: $validModels"),
                            'operator' => $schema
                                ->string()
                                ->required()
                                ->description("The comparison operator. Must be one of: $validOperators"),
                            'value' => $schema
                                ->array()
                                ->items($schema->string())
                                ->required()
                                ->description(
                                    'The filter value(s) as strings. Use ["value"] for single values, ["min", "max"] for isBetween.',
                                ),
                        ]))
                        ->required(),
                ]))
                ->required()
                ->description(
                    'Array of rule groups (OR logic between groups, AND within each group). Must have at least one group with at least one rule.',
                ),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $rawGroups = array_map(static function (array $group): array {
            return [
                'id' => Uuid::generate(),
                'rules' => array_map(static function (array $rule): array {
                    return [
                        'id' => Uuid::generate(),
                        'model' => $rule['model'] ?? '',
                        'operator' => $rule['operator'] ?? 'is',
                        'value' => (array) ($rule['value'] ?? []),
                    ];
                }, $group['rules'] ?? []),
            ];
        }, $request['rule_groups'] ?? []);

        if ($rawGroups === [] || !isset($rawGroups[0]['rules']) || $rawGroups[0]['rules'] === []) {
            return (
                'Error: rule_groups must contain at least one group with at least one rule. '
                . 'Each rule needs model, operator, and value. '
                . 'Valid models: '
                . implode(', ', array_column(SmartPlaylistModel::cases(), 'value'))
                . '. '
                . 'Valid operators: '
                . implode(', ', array_column(SmartPlaylistOperator::cases(), 'value'))
                . '.'
            );
        }

        try {
            $ruleGroups = SmartPlaylistRuleGroupCollection::create($rawGroups);
        } catch (Throwable $e) {
            return (
                'Error creating rules: '
                . $e->getMessage()
                . '. '
                . 'Valid models: '
                . implode(', ', array_column(SmartPlaylistModel::cases(), 'value'))
                . '. '
                . 'Valid operators: '
                . implode(', ', array_column(SmartPlaylistOperator::cases(), 'value'))
                . '. '
                . 'Values must be non-empty string arrays with 1-2 elements.'
            );
        }

        $playlist = $this->playlistService->createPlaylist(
            PlaylistCreateData::make(name: $request['name'], ruleGroups: $ruleGroups),
            $this->context->user,
        );

        $this->result->action = 'create_smart_playlist';
        $this->result->data = [
            'playlist' => $playlist,
        ];

        return sprintf('Smart playlist "%s" created successfully.', $playlist->name);
    }
}
