<?php

namespace App\Console\Commands;

use App\Models\Podcast;
use App\Services\ParallelPodcastSync;
use App\Services\PodcastService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class SyncPodcastsCommand extends Command
{
    protected $signature = 'koel:podcasts:sync
        {--J|jobs= : Number of parallel worker processes}';

    protected $description = 'Synchronize podcasts.';

    public function __construct(
        private readonly PodcastService $podcastService,
        private readonly ParallelPodcastSync $parallelSync,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ids = Podcast::query()->pluck('id')->all();

        if (!$ids) {
            info('No podcasts to sync.');

            return self::SUCCESS;
        }

        $jobs = (int) ($this->option('jobs') ?: config('koel.sync.podcast_jobs', 4));
        $jobs = min(max(1, $jobs), count($ids));

        return $jobs === 1 ? $this->syncSequentially() : $this->syncInParallel($ids, $jobs);
    }

    private function syncSequentially(): int
    {
        Podcast::query()->get()->each(function (Podcast $podcast): void {
            try {
                info(sprintf('Checking "%s" for new content…', $podcast->title));

                if (!$this->podcastService->isPodcastObsolete($podcast)) {
                    warning('└── The podcast feed has not been updated recently, skipping.');

                    return;
                }

                info('└── Synchronizing episodes…');
                $this->podcastService->refreshPodcast($podcast);
            } catch (Throwable $e) {
                Log::error($e);
            }
        });

        return self::SUCCESS;
    }

    private function syncInParallel(array $ids, int $jobs): int
    {
        info(sprintf('Syncing %d podcast(s) with %d parallel workers.', count($ids), $jobs));

        $this->parallelSync->execute($ids, $jobs, static function (object $result): void {
            match ($result->status) {
                'synced' => info(sprintf('Synced "%s"', $result->title)),
                'skipped' => warning(sprintf('Skipped "%s" (feed not updated recently)', $result->title)),
                'error' => error(sprintf('Error syncing "%s": %s', $result->title, $result->error)),
                default => null,
            };
        });

        return self::SUCCESS;
    }
}
