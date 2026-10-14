<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Scanners\ScanIndexer;
use App\Services\Scanners\Strategies\SequentialScanStrategy;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\LazyCollection;

class ScanChunkCommand extends Command
{
    protected $signature = 'koel:scan:chunk
        {manifest : Path to the file listing the paths to scan, one per line}
        {--offset=0 : The number of listed paths to skip}
        {--limit= : The maximum number of listed paths to scan}
        {--owner= : The ID of the song owner}
        {--public : Whether to make songs public}
        {--ignore=* : Tags to ignore}
        {--force : Force re-scanning}';

    protected $description = 'Scan a chunk of files (internal command used by koel:scan for parallel processing).';

    protected $hidden = true;

    public function __construct(
        private readonly SequentialScanStrategy $scanner,
        private readonly ScanIndexer $indexer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $paths = File::lines($this->argument('manifest'))
            ->filter()
            ->skip((int) $this->option('offset'))
            ->when(
                $this->option('limit') !== null,
                fn (LazyCollection $listedPaths): LazyCollection => $listedPaths->take((int) $this->option('limit')),
            );

        /** @var User $owner */
        $owner = User::query()->findOrFail($this->option('owner'));

        $config = ScanConfiguration::make(
            owner: $owner,
            makePublic: (bool) $this->option('public'),
            ignores: collect($this->option('ignore'))->sort()->values()->all(),
            force: (bool) $this->option('force'),
        );

        $this->indexer->withoutSyncingToSearch(fn () => $this->scanner->scan(
            $paths,
            $config,
            function (ScanResult $result): void {
                $this->output->writeln(json_encode([
                    'path' => $result->path,
                    'type' => $result->type->value,
                    'error' => $result->error,
                ]));
            },
        ));

        return self::SUCCESS;
    }
}
