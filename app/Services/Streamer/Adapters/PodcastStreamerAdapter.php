<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song as Episode;
use App\Services\PodcastService;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\Podcast\EpisodePlayable;
use App\Values\RequestedStreamingConfig;
use Webmozart\Assert\Assert;

class PodcastStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(private readonly PodcastService $podcastService)
    {
    }

    /** @inheritDoc */
    public function stream(Episode $song, ?RequestedStreamingConfig $config = null)
    {
        Assert::true($song->isEpisode());

        $streamableUrl = $this->podcastService->getStreamableUrl($song);

        if ($streamableUrl) {
            return response()->redirectTo($streamableUrl);
        }

        $this->streamLocalPath(EpisodePlayable::getForEpisode($song)->path);
    }
}
