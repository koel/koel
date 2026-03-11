<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Repositories\ArtistRepository;
use App\Services\ArtistService;
use App\Values\Artist\ArtistUpdateData;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateArtistDetails implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly ArtistRepository $artistRepository,
        private readonly ArtistService $artistService,
        private Gate $gate,
    ) {
        $this->gate = $this->gate->forUser($this->context->user);
    }

    public function description(): Stringable|string
    {
        return 'Rename an artist. Use this when the user wants to change an artist\'s name.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'current_name' => $schema
                ->string()
                ->required()
                ->description('The current name (or partial name) of the artist to update'),
            'new_name' => $schema->string()->required()->description('The new name for the artist'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $artist = $this->artistRepository->findOneBy([
            'name' => $request['current_name'],
            'user_id' => $this->context->user->id,
        ]);

        if (!$artist) {
            return sprintf('No artist matching "%s" found in your library.', $request['current_name']);
        }

        if ($this->gate->denies('update', $artist)) {
            return sprintf('You don\'t have permission to update "%s".', $artist->name);
        }

        $oldName = $artist->name;

        $artist = $this->artistService->updateArtist($artist, ArtistUpdateData::make(name: $request['new_name']));

        $this->result->action = 'update_artist';
        $this->result->data = ['artist' => $artist];

        return sprintf('Renamed "%s" to "%s".', $oldName, $request['new_name']);
    }
}
