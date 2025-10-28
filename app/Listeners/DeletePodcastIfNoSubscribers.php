<?php

namespace App\Listeners;

use App\Events\UserUnsubscribedFromPodcast;
use App\Services\PodcastService;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class DeletePodcastIfNoSubscribers implements ShouldQueue
{
    public function __construct(private PodcastService $podcastService)
    {
    }

    public function handle(UserUnsubscribedFromPodcast $event): void
    {
        if ($event->podcast->subscribers()->count() === 0) {
            $this->podcastService->deletePodcast($event->podcast);
        }
    }
}
