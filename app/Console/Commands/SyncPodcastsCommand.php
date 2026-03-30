<?php

namespace App\Console\Commands;

use App\Models\Podcast;
use App\Services\PodcastService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class SyncPodcastsCommand extends Command
{
    protected $signature = 'koel:podcasts:sync
        {--J|jobs= : Number of parallel worker processes}';

    protected $description = 'Synchronize podcasts.';

    public function __construct(
        private readonly PodcastService $podcastService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ids = Podcast::query()->pluck('id')->all();

        if (!$ids) {
            $this->info('No podcasts to sync.');

            return self::SUCCESS;
        }

        $jobs = (int) ($this->option('jobs') ?: config('koel.sync.podcast_jobs', 4));
        $jobs = min(max(1, $jobs), count($ids));

        if ($jobs === 1) {
            return $this->syncSequentially();
        }

        return $this->syncInParallel($ids, $jobs);
    }

    private function syncSequentially(): int
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

    private function syncInParallel(array $ids, int $jobs): int
    {
        $chunks = array_chunk($ids, (int) ceil(count($ids) / $jobs));

        $this->info(sprintf('Syncing %d podcast(s) with %d parallel workers.', count($ids), count($chunks)));

        $processes = [];

        foreach ($chunks as $chunk) {
            $command = [PHP_BINARY, base_path('artisan'), 'koel:podcasts:sync-chunk', '--no-interaction', '--no-ansi'];

            foreach ($chunk as $id) {
                $command[] = $id;
            }

            $process = new Process($command, base_path());
            $process->setTimeout(300);
            $process->start();
            $processes[] = $process;
        }

        $this->collectResults($processes);

        return self::SUCCESS;
    }

    /** @param Process[] $processes */
    private function collectResults(array $processes): void
    {
        $buffers = array_fill(0, count($processes), '');

        while ($processes) {
            foreach ($processes as $i => $process) {
                $output = $process->getIncrementalOutput();

                if ($output !== '') {
                    $buffers[$i] .= $output;

                    while (($pos = strpos($buffers[$i], "\n")) !== false) {
                        $this->handleResultLine(substr($buffers[$i], 0, $pos));
                        $buffers[$i] = substr($buffers[$i], $pos + 1);
                    }
                }

                if (!$process->isRunning()) {
                    if (trim($buffers[$i]) !== '') {
                        $this->handleResultLine($buffers[$i]);
                    }

                    unset($processes[$i], $buffers[$i]);
                }
            }

            if ($processes) {
                usleep(50_000);
            }
        }
    }

    private function handleResultLine(string $line): void
    {
        $data = json_decode(trim($line), true);

        if (!$data) {
            return;
        }

        match ($data['status']) {
            'synced' => $this->info("Synced \"{$data['title']}\""),
            'skipped' => $this->warn("Skipped \"{$data['title']}\" (feed not updated recently)"),
            'error' => $this->error("Error syncing \"{$data['title']}\": {$data['error']}"),
            default => null,
        };
    }
}
