<?php

namespace App\Console\Commands;

use App\Facades\Dispatcher;
use App\Jobs\CreateSongPreviewJob;
use App\Repositories\SongRepository;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Config;

class CreateSongPreviewsCommand extends Command
{
    protected $signature = 'koel:create-song-previews';

    protected $description = 'Create 30-second previews of songs that do not have one yet, for embeds in Preview mode.';

    public function __construct(
        private readonly SongRepository $songRepository,
        #[Config('koel.streaming.transcode_timeout')]
        private readonly int $transcodeTimeout,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $total = $this->songRepository->countSongsWithoutPreview();

        if ($total === 0) {
            $this->info('Every song already has a preview.');

            return self::SUCCESS;
        }

        $started = 0;
        $failed = 0;
        $skipped = 0;
        $progress = $this->output->createProgressBar($total);

        foreach ($this->songRepository->lazyGetSongsWithoutPreview() as $song) {
            if (!$song->storage->supported()) {
                $skipped++;
            } elseif (self::dispatchPreviewJob(new CreateSongPreviewJob($song, $this->transcodeTimeout))) {
                $started++;
            } else {
                $failed++;
            }

            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);

        $this->table(['Result', 'Songs'], [
            ['Created or queued',                 $started],
            ['Failed',                            $failed],
            ['Skipped (storage needs Koel Plus)', $skipped],
        ]);

        return self::SUCCESS;
    }

    private static function dispatchPreviewJob(CreateSongPreviewJob $job): bool
    {
        return rescue(static function () use ($job): bool {
            Dispatcher::dispatch($job);

            return true;
        }, false);
    }
}
