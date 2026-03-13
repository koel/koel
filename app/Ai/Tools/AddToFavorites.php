<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Services\FavoriteableEntityResolver;
use App\Enums\FavoriteableType;
use App\Services\FavoriteService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class AddToFavorites implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly FavoriteableEntityResolver $entityResolver,
        private readonly FavoriteService $favoriteService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Add items to the user\'s favorites. '
            . 'Use this when the user wants to like, love, or favorite a song, album, artist, radio station, or podcast. '
            . 'Can favorite the currently playing song or search by name.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema
                ->string()
                ->description(
                    'The type of item to favorite: playable (for songs), album, artist, radio-station, or podcast. Defaults to playable.',
                )
                ->required(),
            'query' => $schema
                ->string()
                ->description(
                    'Search keywords to find items to favorite. '
                    . 'If omitted and type is playable, the currently playing song will be favorited.',
                ),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $type = FavoriteableType::tryFrom($request['type'] ?? '') ?? FavoriteableType::PLAYABLE;
        $entities = $this->entityResolver->resolve($type, $request, $this->context);

        if ($entities->isEmpty()) {
            return (
                "Could not find any {$type->value}(s) to favorite. "
                . 'Please specify a name or make sure a song is currently playing.'
            );
        }

        $this->favoriteService->batchFavorite($entities, $this->context->user);

        $this->result->action = 'add_to_favorites';
        $this->result->data = ['type' => $type, 'entities' => $entities];

        $name = $this->entityResolver->entityName($entities->first());

        if ($entities->count() === 1) {
            return sprintf('Added "%s" to your favorites.', $name);
        }

        return "Added {$entities->count()} {$type->value}(s) to your favorites.";
    }
}
