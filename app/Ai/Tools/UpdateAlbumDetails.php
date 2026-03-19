<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Repositories\AlbumRepository;
use App\Services\AlbumService;
use App\Values\Album\AlbumUpdateData;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateAlbumDetails implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly AlbumRepository $albumRepository,
        private readonly AlbumService $albumService,
        private Gate $gate,
    ) {
        $this->gate = $this->gate->forUser($this->context->user);
    }

    public function description(): Stringable|string
    {
        return 'Update album details such as name or release year. Use this when the user wants to rename an album or change its release year.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->required()
                ->description('The current name (or partial name) of the album to update'),
            'name' => $schema->string()->description('The new name for the album. Omit to keep the current name.'),
            'year' => $schema->integer()->description('The release year of the album. Omit to keep the current value.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $album = $this->albumRepository->findOneBy([
            'name' => $request['query'],
            'user_id' => $this->context->user->id,
        ]);

        if (!$album) {
            return sprintf('No album matching "%s" found in your library.', $request['query']);
        }

        if ($this->gate->denies('update', $album)) {
            return sprintf('You don\'t have permission to update "%s".', $album->name);
        }

        $album = $this->albumService->updateAlbum($album, AlbumUpdateData::make(
            name: $request['name'] ?? $album->name,
            year: array_key_exists('year', $request->only(['year'])) ? $request['year'] : $album->year,
        ));

        $this->result->action = 'update_album';
        $this->result->data = ['album' => $album];

        $changes = [];

        if (isset($request['name'])) {
            $changes[] = sprintf('name to "%s"', $request['name']);
        }

        if (array_key_exists('year', $request->only(['year']))) {
            $changes[] = $request['year'] ? "year to {$request['year']}" : 'removed the release year';
        }

        if (!$changes) {
            return sprintf('No changes were made to "%s".', $album->name);
        }

        return sprintf('Updated "%s": %s.', $album->name, implode(', ', $changes));
    }
}
