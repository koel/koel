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
use Illuminate\Support\Str;
use PhanAn\Poddle\Poddle;
use PhanAn\Poddle\Values\Episode as EpisodeValue;
use PhanAn\Poddle\Values\EpisodeCollection;
use Psr\Http\Client\ClientInterface;
use Throwable;
use Webmozart\Assert\Assert;

class PodcastService
{
    public function __construct(
        private readonly PodcastRepository $podcastRepository,
        private readonly SongRepository $songRepository,
        private ?ClientInterface $client = null,
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
            $parser = $this->createParser($url);
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
        $parser = $this->createParser($podcast->url);
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
            // The pubDate/lastBuildDate value indicates that there's no new content since last check.
            // We'll simply return the podcast.
            return $podcast;
        }

        $this->synchronizeEpisodes($podcast, $parser->getEpisodes(true));

        return $podcast->refresh();
    }

    private function synchronizeEpisodes(Podcast $podcast, EpisodeCollection $episodeCollection): void
    {
        $existingEpisodeGuids = $this->songRepository->getEpisodeGuidsByPodcast($podcast);
        $records = [];
        $ids = [];

        /** @var EpisodeValue $episodeValue */
        foreach ($episodeCollection as $episodeValue) {
            if (!in_array($episodeValue->guid->value, $existingEpisodeGuids, true)) {
                $id = Str::uuid()->toString();
                $ids[] = $id;
                $records[] = [
                    'id' => $id,
                    'podcast_id' => $podcast->id,
                    'title' => $episodeValue->title,
                    'lyrics' => '',
                    'path' => $episodeValue->enclosure->url,
                    'created_at' => $episodeValue->metadata->pubDate ?: now(),
                    'updated_at' => $episodeValue->metadata->pubDate ?: now(),
                    'episode_metadata' => $episodeValue->metadata->toJson(),
                    'episode_guid' => $episodeValue->guid,
                    'length' => $episodeValue->metadata->duration ?? 0,
                    'mtime' => time(),
                    'is_public' => true,
                ];
            }
        }

        // We use insert() instead of $podcast->episodes()->createMany() for better performance,
        // as the latter would trigger a separate query for each episode.
        Episode::insert($records);

        // Since insert() doesn't trigger model events, Scout operations will not be called.
        // We have to manually update the search index.
        Episode::query()->whereIn('id', $ids)->searchable();
    }

    private function subscribeUserToPodcast(User $user, Podcast $podcast): void
    {
        $user->subscribeToPodcast($podcast);

        // Refreshing so that $podcast->subscribers are updated
        $podcast->refresh();
    }

    public function updateEpisodeProgress(User $user, Episode $episode, int $position): void
    {
        Assert::true($user->subscribedToPodcast($episode->podcast));

        /** @var PodcastUserPivot $subscription */
        $subscription = $episode->podcast->subscribers->sole('id', $user->id)->pivot;

        $state = $subscription->state->toArray();
        $state['current_episode'] = $episode->id;
        $state['progresses'][$episode->id] = $position;

        $subscription->update(['state' => $state]);
    }

    public function unsubscribeUserFromPodcast(User $user, Podcast $podcast): void
    {
        $user->unsubscribeFromPodcast($podcast);
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
     * Get a directly streamable (CORS-friendly) URL by following redirects if necessary.
     */
    public function getStreamableUrl(string|Episode $url, ?Client $client = null, string $method = 'OPTIONS'): ?string
    {
        if (!is_string($url)) {
            $url = $url->path;
        }

        $client ??= $this->client ?? new Client();

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

            // Sometimes the podcast server disallows OPTIONS requests. We'll try again with a HEAD request.
            if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500 && $method !== 'HEAD') {
                return $this->getStreamableUrl($url, $client, 'HEAD');
            }

            if (in_array('*', Arr::wrap($response->getHeader('Access-Control-Allow-Origin')), true)) {
                // If there were redirects, the last one is the final URL.
                return $redirects ? Arr::last($redirects) : $url;
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    private function createParser(string $url): Poddle
    {
        return Poddle::fromUrl($url, 5 * 60, $this->client);
    }
}
