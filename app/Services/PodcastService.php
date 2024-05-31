<?php

namespace App\Services;

use App\Exceptions\FailedToParsePodcastFeedException;
use App\Exceptions\UserAlreadySubscribedToPodcast;
use App\Models\Podcast;
use App\Models\PodcastUserPivot;
use App\Models\Song as Episode;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Repositories\SongRepository;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RedirectMiddleware;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhanAn\Poddle\Poddle;
use PhanAn\Poddle\Values\Episode as EpisodeValue;
use PhanAn\Poddle\Values\EpisodeCollection;
use Throwable;

class PodcastService
{
    public function __construct(
        private readonly PodcastRepository $podcastRepository,
        private readonly SongRepository $songRepository
    ) {
    }

    public function addPodcast(string $url, User $user): Podcast
    {
        // Since downloading and parsing a feed can be time-consuming, try setting the execution time to 5 minutes
        @ini_set('max_execution_time', 300);

        $podcast = $this->podcastRepository->findOneByUrl($url);

        if ($podcast) {
            if ($this->isPodcastObsolete($podcast)) {
                $this->refreshPodcast($podcast);
            }

            $this->subscribeUserToPodcast($user, $podcast);

            return $podcast;
        }

        try {
            $parser = Poddle::fromUrl($url, 5 * 60);
            $channel = $parser->getChannel();

            return DB::transaction(function () use ($url, $podcast, $parser, $channel, $user) {
                $podcast = Podcast::query()->create([
                    'url' => $url,
                    'title' => $channel->title,
                    'description' => $channel->description,
                    'author' => $channel->metadata->author,
                    'link' => $channel->link,
                    'language' => $channel->language,
                    'explicit' => $channel->explicit,
                    'image' => $channel->image,
                    'categories' => $channel->categories,
                    'metadata' => $channel->metadata,
                    'added_by' => $user->id,
                    'last_synced_at' => now(),
                ]);

                $this->synchronizeEpisodes($podcast, $parser->getEpisodes(true));
                $this->subscribeUserToPodcast($user, $podcast);

                return $podcast;
            });
        } catch (UserAlreadySubscribedToPodcast $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error($exception);
            throw FailedToParsePodcastFeedException::create($url, $exception);
        }
    }

    public function refreshPodcast(Podcast $podcast): Podcast
    {
        $parser = Poddle::fromUrl($podcast->url);
        $channel = $parser->getChannel();

        $podcast->update([
            'title' => $channel->title,
            'description' => $channel->description,
            'author' => $channel->metadata->author,
            'link' => $channel->link,
            'language' => $channel->language,
            'explicit' => $channel->explicit,
            'image' => $channel->image,
            'categories' => $channel->categories,
            'metadata' => $channel->metadata,
            'last_synced_at' => now(),
        ]);

        $pubDate = $parser->xmlReader->value('rss.channel.pubDate')?->first()
            ?? $parser->xmlReader->value('rss.channel.lastBuildDate')?->first();

        if ($pubDate && Carbon::createFromFormat(Carbon::RFC1123, $pubDate)->isBefore($podcast->last_synced_at)) {
            // The pubDate/lastBuildDate value indicates that there's no new content since last check
            return $podcast->refresh();
        }

        $this->synchronizeEpisodes($podcast, $parser->getEpisodes(true));

        return $podcast->refresh();
    }

    private function synchronizeEpisodes(Podcast $podcast, EpisodeCollection $episodeCollection): void
    {
        $existingEpisodeGuids = $this->songRepository->getEpisodeGuidsByPodcast($podcast);

        /** @var EpisodeValue $episodeValue */
        foreach ($episodeCollection as $episodeValue) {
            if (!in_array($episodeValue->guid->value, $existingEpisodeGuids, true)) {
                $podcast->addEpisodeByDTO($episodeValue);
            }
        }
    }

    private function subscribeUserToPodcast(User $user, Podcast $podcast): void
    {
        $user->subscribeToPodcast($podcast);

        // Refreshing so that $podcast->subscribers are updated
        $podcast->refresh();
    }

    public function updateEpisodeProgress(User $user, Episode $episode, int $position): void
    {
        /** @var PodcastUserPivot $subscription */
        $subscription = $episode->podcast->subscribers->sole('id', $user->id)->pivot;

        $state = $subscription->state->toArray();
        $state['current_episode'] = $episode->id;
        $state['progresses'][$episode->id] = $position;

        $subscription->update(['state' => $state]);
    }

    public function unsubscribeUserFromPodcast(User $user, Podcast $podcast): void
    {
        $podcast->subscribers()->detach($user);
    }

    public function isPodcastObsolete(Podcast $podcast): bool
    {
        if ($podcast->last_synced_at->diffInHours(now()) < 12) {
            // If we have recently synchronized the podcast, consider it "fresh"
            return false;
        }

        try {
            $lastModified = Http::send('HEAD', $podcast->url)->header('Last-Modified');

            return $lastModified
                && Carbon::createFromFormat(Carbon::RFC1123, $lastModified)->isAfter($podcast->last_synced_at);
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * Get a directly streamable (CORS-friendly) from a given URL by following redirects if necessary.
     */
    public function getStreamableUrl(string|Episode $url, ?Client $client = null, string $method = 'OPTIONS'): ?string
    {
        if (!is_string($url)) {
            $url = $url->path;
        }

        $client ??= new Client();

        try {
            $response = $client->request($method, $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15', // @phpcs-ignore-line
                    'Origin' => '*',
                ],
                'http_errors' => false,
                'allow_redirects' => ['track_redirects' => true],
            ]);

            $redirects = Arr::wrap($response->getHeader(RedirectMiddleware::HISTORY_HEADER));

            // If there were redirects, we make the CORS check on the last URL, as it
            // would be the one eventually used by the browser.
            if ($redirects) {
                return $this->getStreamableUrl(Arr::last($redirects), $client);
            }

            // Sometimes the podcast server disallows OPTIONS requests. We'll try again with a HEAD request.
            if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500 && $method !== 'HEAD') {
                return $this->getStreamableUrl($url, $client, 'HEAD');
            }

            if (in_array('*', Arr::wrap($response->getHeader('Access-Control-Allow-Origin')), true)) {
                return $url;
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }
}
