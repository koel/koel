<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song as Episode;
use App\Services\Network\SafeHttp;
use App\Services\Podcast\PodcastService;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\Podcast\EpisodePlayable;
use App\Values\RequestedStreamingConfig;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webmozart\Assert\Assert;

class PodcastStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(
        private readonly PodcastService $podcastService,
        private readonly SafeHttp $http,
    ) {}

    /** @inheritDoc */
    public function stream(Episode $song, ?RequestedStreamingConfig $config = null): BinaryFileResponse|RedirectResponse
    {
        Assert::true($song->isEpisode());

        $streamableUrl = $this->podcastService->getStreamableUrl($song);

        if ($streamableUrl) {
            return response()->redirectTo($streamableUrl);
        }

        return self::streamLocalPath(EpisodePlayable::getForEpisode($song, $this->http)->path);
    }
}
