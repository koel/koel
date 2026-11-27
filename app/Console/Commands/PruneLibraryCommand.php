<?php

namespace App\Console\Commands;

use App\Services\LibraryManager;
use Illuminate\Console\Command;

class PruneLibraryCommand extends Command
{
    protected $signature = 'koel:prune {--dry-run}';
    protected $description = 'Remove empty artists, albums, and genres';

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
            $this->info('Dry run: no changes made.');

            $this->info(
                "Found {$results['artists']->count()} empty artist(s), {$results['albums']->count()} empty album(s), and {$results['genres']->count()} empty genre(s).",
            );

            foreach ($results['artists'] as $result) {
                $this->line("Artist: {$result->name} (ID: {$result->id})");
            }

            foreach ($results['albums'] as $result) {
                $this->line("Album: {$result->name} (ID: {$result->id}})");
            }

            foreach ($results['genres'] as $result) {
                $this->line("Genre: {$result->name} (ID: {$result->public_id})");
            }

            return self::SUCCESS;
        }

        $this->info(
            "{$results['artists']->count()} empty artist(s), {$results['albums']->count()} album(s), and {$results['genres']->count()} genre(s) removed.",
        );

        return self::SUCCESS;
    }
}
