<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Repositories\PodcastRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayLatestPodcastEpisode implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly PodcastRepository $podcastRepository,
        private readonly SongRepository $songRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play the newest episode of a podcast the user is subscribed to. '
            . 'Use this when the user wants to hear the latest or newest episode of a podcast.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'podcast' => $schema->string()->required()->description('The name of the podcast'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $podcast = $this->podcastRepository->search($request['podcast'], 1, $this->context->user)->first();

        if (!$podcast) {
            return sprintf('The user is not subscribed to a podcast matching "%s".', $request['podcast']);
        }

        $episode = $this->songRepository->getEpisodesByPodcast($podcast, $this->context->user)->first();

        if (!$episode) {
            return sprintf('The podcast "%s" has no episodes.', $podcast->title);
        }

        $this->playbackService->playEpisode($episode);

        return sprintf('Playing "%s" from "%s".', $episode->title, $podcast->title);
    }
}
