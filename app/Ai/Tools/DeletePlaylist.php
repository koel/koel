<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Repositories\PlaylistRepository;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DeletePlaylist implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly PlaylistRepository $playlistRepository,
        private Gate $gate,
    ) {
        $this->gate = $this->gate->forUser($this->context->user);
    }

    public function description(): Stringable|string
    {
        return 'Delete a playlist. Use this when the user wants to delete or remove a playlist entirely.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'playlist_name' => $schema
                ->string()
                ->required()
                ->description('The name (or partial name) of the playlist to delete'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $playlist = $this->playlistRepository->findAccessibleByName($request['playlist_name'], $this->context->user);

        if (!$playlist) {
            return "No playlist matching \"{$request['playlist_name']}\" found.";
        }

        if ($this->gate->denies('own', $playlist)) {
            return "You don't have permission to delete \"{$playlist->name}\".";
        }

        $name = $playlist->name;
        $playlist->delete();

        return "Deleted the playlist \"{$name}\".";
    }
}
