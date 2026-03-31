<?php

namespace App\Console\Commands;

use App\Services\LibraryManager;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;

class PruneLibraryCommand extends Command
{
    protected $signature = 'koel:prune {--dry-run}';
    protected $description = 'Remove empty artists and albums';

    public function __construct(
        private readonly LibraryManager $libraryManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $results = $this->libraryManager->prune($dryRun);

        if ($dryRun) {
            info('Dry run: no changes made.');

            info(
                "Found {$results['artists']->count()} empty artist(s) and {$results['albums']->count()} empty album(s).",
            );

            foreach ($results['artists'] as $result) {
                info("Artist: {$result->name} (ID: {$result->id})");
            }

            foreach ($results['albums'] as $result) {
                info("Album: {$result->name} (ID: {$result->id}})");
            }

            return self::SUCCESS;
        }

        info("{$results['artists']->count()} empty artist(s) and {$results['albums']->count()} albums removed.");

        return self::SUCCESS;
    }
}
