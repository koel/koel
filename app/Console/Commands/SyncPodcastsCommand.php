<?php

namespace App\Console\Commands;

use App\Models\Podcast\Podcast;
use App\Services\PodcastService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncPodcastsCommand extends Command
{
    protected $signature = 'koel:podcasts:sync';

    public function __construct(private readonly PodcastService $podcastService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Podcast::query()->get()->each(function (Podcast $podcast): void {
            try {
                $this->info("Checking \"$podcast->title\" for new content…");

                if (!$this->podcastService->isPodcastObsolete($podcast)) {
                    $this->warn('└── The podcast feed has not been updated recently, skipping.');

                    return;
                }

                $this->info('└── Synchronizing episodes…');
                $this->podcastService->refreshPodcast($podcast);
            } catch (Throwable $e) {
                Log::error($e);
            }
        });

        return self::SUCCESS;
    }
}
