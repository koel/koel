<?php

namespace App\Ai\Tools;

use App\Models\User;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use App\Services\EncyclopediaService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetArtistInfo implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly ArtistRepository $artistRepository,
        private readonly SongRepository $songRepository,
        private readonly EncyclopediaService $encyclopediaService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Get information about an artist, including their biography and library stats. '
            . 'Use this when the user asks about an artist, wants to know more about them, or asks "who is [artist]".'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The artist name to look up'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $artists = $this->artistRepository->search($request['name'], 1, $this->user);

        if ($artists->isEmpty()) {
            return "No artist matching \"{$request['name']}\" found in the library.";
        }

        $artist = $artists->first();
        $songs = $this->songRepository->getByArtist($artist, $this->user);
        $info = $this->encyclopediaService->getArtistInformation($artist);

        $response = "\"{$artist->name}\" — {$songs->count()} song(s) in your library.";

        if ($info && $info->bio['summary']) {
            $bio = strip_tags($info->bio['summary']);
            $response .= "\n\nBio: $bio";
        }

        if ($info?->url) {
            $response .= "\n\nMore info: {$info->url}";
        }

        return $response;
    }
}
