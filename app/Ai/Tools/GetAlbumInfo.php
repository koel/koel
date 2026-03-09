<?php

namespace App\Ai\Tools;

use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Services\EncyclopediaService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetAlbumInfo implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
        private readonly EncyclopediaService $encyclopediaService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Get information about an album, including its description, track listing, and library stats. '
            . 'Use this when the user asks about an album or wants to know more about it.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The album name to look up'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $albums = $this->albumRepository->search($request['name'], 1, $this->user);

        if ($albums->isEmpty()) {
            return "No album matching \"{$request['name']}\" found in the library.";
        }

        $album = $albums->first();
        $songs = $this->songRepository->getByAlbum($album, $this->user);
        $info = $this->encyclopediaService->getAlbumInformation($album);

        $response = "\"{$album->name}\" by {$album->artist->name} — {$songs->count()} song(s) in your library.";

        if ($info && $info->wiki['summary']) {
            $wiki = strip_tags($info->wiki['summary']);
            $response .= "\n\n$wiki";
        }

        if ($info && count($info->tracks)) {
            $trackList = collect($info->tracks)
                ->map(static fn (array $track, int $i): string => ($i + 1) . ". {$track['title']}")
                ->implode("\n");
            $response .= "\n\nTrack listing:\n$trackList";
        }

        if ($info?->url) {
            $response .= "\n\nMore info: {$info->url}";
        }

        return $response;
    }
}
