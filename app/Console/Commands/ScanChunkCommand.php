<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Scanners\Strategies\SequentialScanStrategy;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SplFileInfo;

class ScanChunkCommand extends Command
{
    protected $signature = 'koel:scan:chunk
        {manifest : Path to the manifest file containing file paths to scan}
        {--owner= : The ID of the song owner}
        {--public : Whether to make songs public}
        {--ignore=* : Tags to ignore}
        {--force : Force re-scanning}';

    protected $description = 'Scan a chunk of files (internal command used by koel:scan for parallel processing).';

    protected $hidden = true;

    public function __construct(
        private readonly SequentialScanStrategy $scanner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $manifestPath = $this->argument('manifest');

        if (!File::exists($manifestPath)) {
            $this->components->error("Manifest file not found: $manifestPath");

            return self::FAILURE;
        }

        $paths = json_decode(File::get($manifestPath), true);

        if (!is_array($paths)) {
            $this->components->error("Malformed manifest file: $manifestPath");

            return self::FAILURE;
        }

        /** @var User $owner */
        $owner = User::query()->findOrFail($this->option('owner'));

        $config = ScanConfiguration::make(
            owner: $owner,
            makePublic: (bool) $this->option('public'),
            ignores: collect($this->option('ignore'))->sort()->values()->all(),
            force: (bool) $this->option('force'),
        );

        $files = array_map(static fn (string $path) => new SplFileInfo($path), $paths);

        $this->scanner->scan($files, $config, function (ScanResult $result): void {
            $this->output->writeln(json_encode([
                'path' => $result->path,
                'type' => $result->type->value,
                'error' => $result->error,
            ]));
        });

        return self::SUCCESS;
    }
}
