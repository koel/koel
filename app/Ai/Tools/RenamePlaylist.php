<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Repositories\PlaylistRepository;
use App\Services\PlaylistService;
use App\Values\Playlist\PlaylistUpdateData;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RenamePlaylist implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly PlaylistRepository $playlistRepository,
        private readonly PlaylistService $playlistService,
        private Gate $gate,
    ) {
        $this->gate = $this->gate->forUser($this->context->user);
    }

    public function description(): Stringable|string
    {
        return 'Rename an existing playlist. Use this when the user wants to change a playlist\'s name.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'current_name' => $schema
                ->string()
                ->required()
                ->description('The current name (or partial name) of the playlist to rename'),
            'new_name' => $schema->string()->required()->description('The new name for the playlist'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $playlist = $this->playlistRepository->searchAccessibleByName($request['current_name'], $this->context->user);

        if (!$playlist) {
            return sprintf('No playlist matching "%s" found.', $request['current_name']);
        }

        if ($this->gate->denies('own', $playlist)) {
            return sprintf('You don\'t have permission to rename "%s".', $playlist->name);
        }

        $oldName = $playlist->name;

        $this->playlistService->updatePlaylist($playlist, PlaylistUpdateData::make(
            name: $request['new_name'],
            description: $playlist->description ?? '',
        ));

        return sprintf('Renamed "%s" to "%s".', $oldName, $request['new_name']);
    }
}
