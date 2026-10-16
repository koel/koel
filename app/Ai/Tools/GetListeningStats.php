<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Enums\ListeningPeriod;
use App\Services\ListeningStatisticsService;
use Carbon\CarbonInterval;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetListeningStats implements Tool
{
    private const int TOP_ENTRY_COUNT = 5;

    public function __construct(
        private readonly AiRequestContext $context,
        private readonly ListeningStatisticsService $listeningStatisticsService,
        #[Config('app.timezone')]
        private readonly string $timezone,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Get the user\'s listening statistics for a period: how much they listened, '
            . 'and their top songs, artists, albums and genres. '
            . 'Use this when the user asks what they listened to most, how much they listened, '
            . 'or who their top artists are. This only answers; it does not play anything.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema
                ->string()
                ->enum(array_column(ListeningPeriod::cases(), 'value'))
                ->description('The period to look at: the last week, month, year, or all time. Default "month".'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $period = ListeningPeriod::tryFrom($request['period'] ?? '') ?? ListeningPeriod::MONTH;
        $statistics = $this->listeningStatisticsService->getStatistics($this->context->user, $period, $this->timezone);

        if (!$statistics->summary->plays) {
            return 'No listening history for this period.';
        }

        $lines = [
            sprintf(
                '%d play(s), %s of listening, %d different song(s) by %d artist(s).',
                $statistics->summary->plays,
                self::formatDuration($statistics->summary->listeningTime),
                $statistics->summary->songCount,
                $statistics->summary->artistCount,
            ),
            self::formatTopList('Top songs', $statistics->topSongs, static fn (array $entry) => sprintf(
                '%s by %s (%d play(s))',
                $entry['song']->title,
                $entry['song']->artist_name,
                $entry['plays'],
            )),
            self::formatTopList('Top artists', $statistics->topArtists, static fn (array $entry) => sprintf(
                '%s (%s)',
                $entry['artist']->name,
                self::formatDuration($entry['listening_time']),
            )),
            self::formatTopList('Top albums', $statistics->topAlbums, static fn (array $entry) => sprintf(
                '%s by %s (%s)',
                $entry['album']->name,
                $entry['album']->artist->name,
                self::formatDuration($entry['listening_time']),
            )),
            self::formatTopList('Top genres', $statistics->topGenres, static fn (array $entry) => sprintf(
                '%s (%s)',
                $entry['name'],
                self::formatDuration($entry['listening_time']),
            )),
        ];

        return implode("\n\n", array_filter($lines));
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private static function formatTopList(string $heading, array $entries, callable $formatEntry): string
    {
        if (!$entries) {
            return '';
        }

        $items = collect($entries)
            ->take(self::TOP_ENTRY_COUNT)
            ->map(static fn (array $entry) => '- ' . $formatEntry($entry))
            ->implode("\n");

        return "$heading:\n$items";
    }

    private static function formatDuration(float $seconds): string
    {
        return CarbonInterval::seconds($seconds)->cascade()->forHumans(['parts' => 2]);
    }
}
