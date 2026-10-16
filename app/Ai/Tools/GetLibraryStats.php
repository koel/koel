<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Services\LibrarySummaryService;
use App\Values\GenreSummary;
use Carbon\CarbonInterval;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetLibraryStats implements Tool
{
    private const int TOP_GENRE_COUNT = 10;

    public function __construct(
        private readonly AiRequestContext $context,
        private readonly LibrarySummaryService $librarySummaryService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Get the size of the user\'s music library: how many songs, their total length, and the biggest genres. '
            . 'Use this when the user asks how much music they have, how long it would take to listen to everything, '
            . 'or which genres make up their library.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = $this->context->user;
        $songCount = $this->librarySummaryService->getSongCount($user);

        if (!$songCount) {
            return 'The library has no songs yet.';
        }

        $totalLength = CarbonInterval::seconds($this->librarySummaryService->getTotalSongLength($user))
            ->cascade()
            ->forHumans(['parts' => 2]);

        $topGenres = $this->librarySummaryService
            ->getGenreSummaries($user)
            ->sortByDesc(static fn (GenreSummary $genre) => $genre->songCount)
            ->take(self::TOP_GENRE_COUNT)
            ->map(static fn (GenreSummary $genre) => sprintf('- %s: %d song(s)', $genre->name, $genre->songCount))
            ->implode("\n");

        $response = sprintf('%d song(s), %s of music in total.', $songCount, $totalLength);

        if ($topGenres) {
            $response .= "\n\nBiggest genres:\n$topGenres";
        }

        return $response;
    }
}
