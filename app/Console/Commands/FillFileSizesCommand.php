<?php

namespace App\Console\Commands;

use App\Repositories\SongRepository;
use App\Services\LibrarySummaryService;
use App\Services\SongService;
use Illuminate\Console\Command;

class FillFileSizesCommand extends Command
{
    protected $signature = 'koel:fill-file-sizes';

    protected $description = 'Record the file size of songs that do not have one yet.';

    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly SongService $songService,
        private readonly LibrarySummaryService $librarySummaryService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $total = $this->songRepository->countSongsWithoutFileSize();

        if ($total === 0) {
            $this->info('Every song already has a file size.');

            return self::SUCCESS;
        }

        $filled = 0;
        $missing = 0;
        $skipped = 0;
        $progress = $this->output->createProgressBar($total);

        foreach ($this->songRepository->lazyGetSongsWithoutFileSize() as $song) {
            if (!$song->storage->supported()) {
                $skipped++;
            } elseif ($this->songService->fillMissingFileSize($song)) {
                $filled++;
            } else {
                $missing++;
            }

            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);

        if ($filled > 0) {
            $this->librarySummaryService->markLibraryChanged();
        }

        $this->table(['Result', 'Songs'], [
            ['Filled',                            $filled],
            ['File not found',                    $missing],
            ['Skipped (storage needs Koel Plus)', $skipped],
        ]);

        return self::SUCCESS;
    }
}
