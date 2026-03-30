<?php

namespace App\Console\Commands;

use App\Models\Podcast;
use App\Services\PodcastService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncPodcastsChunkCommand extends Command
{
    protected $signature = 'koel:podcasts:sync-chunk {ids* : The podcast IDs to sync}';
    protected $description = 'Sync a chunk of podcasts (internal command used by koel:podcasts:sync for parallel processing).';
    protected $hidden = true;

    public function __construct(
        private readonly PodcastService $podcastService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $podcasts = Podcast::query()->whereIn('id', $this->argument('ids'))->get();

        foreach ($podcasts as $podcast) {
            try {
                if (!$this->podcastService->isPodcastObsolete($podcast)) {
                    $this->outputResult($podcast, 'skipped');
                    continue;
                }

                $this->podcastService->refreshPodcast($podcast);
                $this->outputResult($podcast, 'synced');
            } catch (Throwable $e) {
                Log::error($e);
                $this->outputResult($podcast, 'error', $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function outputResult(Podcast $podcast, string $status, ?string $error = null): void
    {
        $this->output->writeln(json_encode([
            'id' => $podcast->id,
            'title' => $podcast->title,
            'status' => $status,
            'error' => $error,
        ], JSON_UNESCAPED_UNICODE));
    }
}
