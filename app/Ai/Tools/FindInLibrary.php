<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FindInLibrary implements Tool
{
    private const int RESULT_LIMIT = 25;

    public function __construct(
        private readonly AiRequestContext $context,
        private readonly SongRepository $songRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'List songs, albums or artists in the user\'s library without playing anything. '
            . 'Use this when the user asks whether they have something (e.g. "do I have anything by Björk?") '
            . 'or wants to see what matches (e.g. "which albums are from 1997?"). '
            . 'To find albums by release year, set from_year and to_year; add a query to narrow them down further.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(['songs', 'albums', 'artists'])->required()->description('What to list'),
            'query' => $schema
                ->string()
                ->description('Search keywords, such as an artist name, album name or song title'),
            'from_year' => $schema->integer()->description('Albums only: the first release year to include'),
            'to_year' => $schema->integer()->description('Albums only: the last release year to include'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $lines = match ($request['type']) {
            'songs' => $this->findSongs($request),
            'albums' => $this->findAlbums($request),
            'artists' => $this->findArtists($request),
            default => [],
        };

        if (!$lines) {
            return 'Nothing in the library matches.';
        }

        $response = sprintf(
            "Found %d result(s):\n%s",
            count($lines),
            implode("\n", array_map(static fn (string $line) => "- $line", $lines)),
        );

        if (count($lines) === self::RESULT_LIMIT) {
            $response .= sprintf("\n\nOnly the first %d are listed; there may be more.", self::RESULT_LIMIT);
        }

        return $response;
    }

    /** @return list<string> */
    private function findSongs(Request $request): array
    {
        if (!isset($request['query'])) {
            return [];
        }

        return $this->songRepository
            ->search($request['query'], self::RESULT_LIMIT, $this->context->user)
            ->map(static fn (Song $song) => sprintf(
                '%s by %s, on %s',
                $song->title,
                $song->artist_name,
                $song->album_name,
            ))
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function findAlbums(Request $request): array
    {
        $query = $request['query'] ?? null;
        $hasYearRange = isset($request['from_year']) || isset($request['to_year']);

        if (!$hasYearRange && !$query) {
            return [];
        }

        $fromYear = (int) ($request['from_year'] ?? $request['to_year']);
        $toYear = (int) ($request['to_year'] ?? $request['from_year']);

        $albums = match (true) {
            !$hasYearRange => $this->albumRepository->search($query, self::RESULT_LIMIT, $this->context->user),
            !$query => $this->albumRepository->getByYearRange(
                fromYear: $fromYear,
                toYear: $toYear,
                size: self::RESULT_LIMIT,
                user: $this->context->user,
            ),
            default => $this->albumRepository
                ->search($query, PHP_INT_MAX, $this->context->user)
                ->filter(
                    static fn (Album $album) => (
                        $album->year >= min($fromYear, $toYear)
                        && $album->year <= max($fromYear, $toYear)
                    ),
                )
                ->take(self::RESULT_LIMIT),
        };

        return $albums
            ->map(static fn (Album $album) => sprintf(
                '%s by %s%s',
                $album->name,
                $album->artist->name,
                $album->year ? " ($album->year)" : '',
            ))
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function findArtists(Request $request): array
    {
        if (!isset($request['query'])) {
            return [];
        }

        return $this->artistRepository
            ->search($request['query'], self::RESULT_LIMIT, $this->context->user)
            ->map(static fn (Artist $artist) => $artist->name)
            ->values()
            ->all();
    }
}
