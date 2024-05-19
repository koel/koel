<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song as Episode;
use App\Services\PodcastService;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\Podcast\EpisodePlayable;
use Illuminate\Http\RedirectResponse;
use Webmozart\Assert\Assert;

class PodcastStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(private readonly PodcastService $podcastService)
    {
    }

    public function stream(Episode $song, array $config = []): RedirectResponse
    {
        Assert::true($song->isEpisode());

        $streamableUrl = $this->podcastService->getStreamableUrl($song);

        if ($streamableUrl) {
            return response()->redirectTo($streamableUrl);
        }

        $playable = EpisodePlayable::retrieveForEpisode($song);

        if (!$playable?->valid()) {
            $playable = EpisodePlayable::createForEpisode($song);
        }

        $this->streamLocalPath($playable->path);
    }
}
