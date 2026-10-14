<?php

namespace App\Console\Commands;

use App\Repositories\AlbumRepository;
use App\Services\AlbumService;
use Illuminate\Console\Command;

class GenerateSmallCoversCommand extends Command
{
    protected $signature = 'koel:generate-small-covers';

    protected $description = 'Generate the small album covers used in lists, for albums that have none yet.';

    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly AlbumService $albumService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $progress = $this->output->createProgressBar($this->albumRepository->countWithoutSmallCover());

        foreach ($this->albumRepository->lazyGetWithoutSmallCover() as $album) {
            rescue(fn () => $this->albumService->generateAlbumSmallCover($album));
            $progress->advance();
        }

        $progress->finish();
        $this->newLine();

        return self::SUCCESS;
    }
}
