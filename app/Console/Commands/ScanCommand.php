<?php

namespace App\Console\Commands;

use App\Libraries\WatchRecord\InotifyWatchRecord;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\MediaScanner;
use App\Values\ScanConfiguration;
use App\Values\ScanResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;

class ScanCommand extends Command
{
    protected $signature = 'koel:scan
        {record? : A single watch record. Consult Wiki for more info.}
        {--O|owner= : The ID of the user who should own the newly scanned songs. Defaults to the first admin user.}
        {--P|private : Whether to make the newly scanned songs private to the user.}
        {--V|verbose : Show more details about the scanning process
        {--I|ignore=* : The comma-separated tags to ignore (exclude) from scanning}
        {--F|force : Force re-scanning even unchanged files}';

    protected $description = 'Scan for songs in the configured directory.';

    private ?string $mediaPath;
    private ProgressBar $progressBar;

    public function __construct(private MediaScanner $mediaScanner, private UserRepository $userRepository)
    {
        parent::__construct();

        $this->mediaScanner->on('paths-gathered', function (array $paths): void {
            $this->progressBar = new ProgressBar($this->output, count($paths));
        });

        $this->mediaScanner->on('progress', [$this, 'onScanProgress']);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setAliases(['koel:sync']);
    }

    public function handle(): int
    {
        if (config('koel.storage_driver') !== 'local') {
            $this->components->error('This command only works with the local storage driver.');

            return self::INVALID;
        }

        $this->mediaPath = $this->getMediaPath();

        $config = ScanConfiguration::make(
            owner:  $this->getOwner(),
            // When scanning via CLI, the songs should be public by default, unless explicitly specified otherwise.
            makePublic: !$this->option('private'),
            ignores: collect($this->option('ignore'))->sort()->values()->all(),
            force: $this->option('force')
        );

        $record = $this->argument('record');

        if ($record) {
            $this->scanSingleRecord($record, $config);
        } else {
            $this->scanMediaPath($config);
        }

        return self::SUCCESS;
    }

    /**
     * Scan all files in the configured media path.
     */
    private function scanMediaPath(ScanConfiguration $config): void
    {
        $this->components->info('Scanning ' . $this->mediaPath);

        if ($config->ignores) {
            $this->components->info('Ignoring tag(s): ' . implode(', ', $config->ignores));
        }

        $results = $this->mediaScanner->scan($config);

        $this->newLine(2);
        $this->components->info('Scanning completed!');

        $this->components->bulletList([
            "<fg=green>{$results->success()->count()}</> new or updated song(s)",
            "<fg=yellow>{$results->skipped()->count()}</> unchanged song(s)",
            "<fg=red>{$results->error()->count()}</> invalid file(s)",
        ]);
    }

    /**
     * @param string $record The watch record.
     *                       As of current we only support inotifywait.
     *                       Some examples:
     *                       - "DELETE /var/www/media/gone.mp3"
     *                       - "CLOSE_WRITE,CLOSE /var/www/media/new.mp3"
     *                       - "MOVED_TO /var/www/media/new_dir"
     *
     * @see http://man7.org/linux/man-pages/man1/inotifywait.1.html
     */
    private function scanSingleRecord(string $record, ScanConfiguration $config): void
    {
        $this->mediaScanner->scanWatchRecord(new InotifyWatchRecord($record), $config);
    }

    public function onScanProgress(ScanResult $result): void
    {
        if (!$this->option('verbose')) {
            $this->progressBar->advance();

            return;
        }

        $path = trim(Str::replaceFirst($this->mediaPath, '', $result->path), DIRECTORY_SEPARATOR);

        $this->components->twoColumnDetail($path, match (true) {
            $result->isSuccess() => "<fg=green>OK</>",
            $result->isSkipped() => "<fg=yellow>SKIPPED</>",
            $result->isError() => "<fg=red>ERROR</>",
            default => throw new RuntimeException("Unknown scan result type: {$result->type}")
        });

        if ($result->isError()) {
            $this->output->writeln("<fg=red>$result->error</>");
        }
    }

    private function getMediaPath(): string
    {
        $path = Setting::get('media_path');

        if ($path) {
            return $path;
        }

        $this->warn("Media path hasn't been configured. Let's set it up.");

        while (true) {
            $path = $this->ask('Absolute path to your media directory');

            if (File::isDirectory($path) && File::isReadable($path)) {
                Setting::set('media_path', $path);
                break;
            }

            $this->error('The path does not exist or is not readable. Try again.');
        }

        return $path;
    }

    private function getOwner(): User
    {
        $specifiedOwner = $this->option('owner');

        if ($specifiedOwner) {
            /** @var User $user */
            $user = User::findOr($specifiedOwner, function () use ($specifiedOwner): void {
                $this->components->error("User with ID $specifiedOwner does not exist.");
                exit(self::INVALID);
            });

            $this->components->info("Setting owner to $user->name (ID $user->id).");

            return $user;
        }

        $user = $this->userRepository->getDefaultAdminUser();

        $this->components->warn(
            "No song owner specified. Setting the first admin ($user->name, ID $user->id) as owner."
        );

        return $user;
    }
}
