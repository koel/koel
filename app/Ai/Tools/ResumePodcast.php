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

class ResumePodcast implements Tool
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
            'Continue the podcast episode the user last listened to, from where they stopped. '
            . 'Use this when the user wants to resume, continue, or pick up a podcast where they left off.'
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
        $user = $this->context->user;
        $podcast = $this->podcastRepository->search($request['podcast'], 1, $user)->first();

        if (!$podcast) {
            return sprintf('The user is not subscribed to a podcast matching "%s".', $request['podcast']);
        }

        $state = $podcast->subscribers->sole('id', $user->id)->pivot->state;
        $episode = $state->currentEpisode ? $this->songRepository->findOne($state->currentEpisode, $user) : null;

        if (!$episode) {
            return sprintf('The user has not started any episode of "%s" yet.', $podcast->title);
        }

        $position = (int) $state->progresses->get($episode->id, 0);
        $finished = $episode->length > 0 && $position >= $episode->length;

        $this->playbackService->playEpisode($episode, $finished ? 0 : $position);

        return sprintf('Continuing "%s" from "%s".', $episode->title, $podcast->title);
    }
}
