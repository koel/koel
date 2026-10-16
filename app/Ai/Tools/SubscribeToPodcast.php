<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Exceptions\ApplePodcastsFeedNotFoundException;
use App\Exceptions\FailedToParsePodcastFeedException;
use App\Exceptions\UserAlreadySubscribedToPodcastException;
use App\Rules\SafeUrl;
use App\Services\Podcast\PodcastService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SubscribeToPodcast implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly PodcastService $podcastService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Subscribe the user to a podcast by its feed URL or Apple Podcasts link. '
            . 'Use this when the user wants to subscribe to or add a podcast. '
            . 'The user must provide the URL; podcasts cannot be found by name.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->required()->description('The podcast\'s RSS feed URL or Apple Podcasts link'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $validator = Validator::make(['url' => $request['url']], ['url' => ['required', 'url', new SafeUrl()]]);

        if ($validator->fails()) {
            return sprintf('Cannot subscribe to the podcast: %s', $validator->errors()->first('url'));
        }

        try {
            $podcast = $this->podcastService->addPodcast($request['url'], $this->context->user);
        } catch (UserAlreadySubscribedToPodcastException) {
            return 'The user is already subscribed to this podcast.';
        } catch (ApplePodcastsFeedNotFoundException) {
            return 'Could not find this podcast\'s feed on Apple Podcasts.';
        } catch (FailedToParsePodcastFeedException) {
            return 'Could not read a podcast feed at this URL.';
        }

        $this->result->action = 'add_podcast';
        $this->result->data = ['podcast' => $podcast];

        return sprintf('Subscribed to the podcast "%s".', $podcast->title);
    }
}
