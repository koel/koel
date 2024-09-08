<?php

namespace App\Console\Commands;

use App\Enums\SongStorageType;
use App\Facades\License;
use App\Http\Integrations\Lastfm\LastfmConnector;
use App\Http\Integrations\Lastfm\Requests\GetArtistInfoRequest;
use App\Http\Integrations\Spotify\SpotifyClient;
use App\Http\Integrations\YouTube\Requests\SearchVideosRequest;
use App\Http\Integrations\YouTube\YouTubeConnector;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use App\Services\SongStorages\SongStorage;
use App\Services\SpotifyService;
use App\Services\YouTubeService;
use Closure;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;
use TiBeN\CrontabManager\CrontabAdapter;
use TiBeN\CrontabManager\CrontabRepository;

class DoctorCommand extends Command
{
    protected $signature = 'koel:doctor';
    protected $description = 'Check Koel setup';

    private array $errors = [];

    public function handle(): int
    {
        if (PHP_OS_FAMILY === 'Windows' || PHP_OS_FAMILY === 'Unknown') {
            $this->components->error('This command is only available on Linux systems.');

            return self::FAILURE;
        }

        $this->components->alert('Checking Koel setup...');
        $this->line('');

        if (exec('whoami') === 'root') {
            $this->components->error('This command cannot be run as root.');

            return self::FAILURE;
        }

        $this->checkFrameworkDirectoryPermissions();
        $this->checkMediaStorage();
        $this->checkDatabaseConnection();
        $this->checkFullTextSearch();
        $this->checkApiHealth();
        $this->checkFFMpeg();
        $this->checkPhpExtensions();
        $this->checkPhpConfiguration();
        $this->checkStreamingMethod();
        $this->checkServiceIntegrations();
        $this->checkMailConfiguration();
        $this->checkScheduler();
        $this->checkPlusLicense();

        if ($this->errors) {
            $this->reportErroneousResult();
        } else {
            $this->output->success('Your Koel setup should be good to go!');
        }

        return self::SUCCESS;
    }

    private function reportErroneousResult(): void
    {
        $this->components->error('There are errors in your Koel setup. Koel will not work properly.');

        if (File::isWritable(base_path('storage/logs/laravel.log'))) {
            /** @var Throwable $error */
            foreach ($this->errors as $error) {
                Log::error('[KOEL.DOCTOR] ' . $error->getMessage(), ['error' => $error]);
            }

            $this->components->error('You can find more details in ' . base_path('storage/logs/laravel.log'));
        } else {
            $this->components->error('The list of errors is as follows:');

            /** @var Throwable $error */
            foreach ($this->errors as $i => $error) {
                $this->line("  <error>[$i]</error> " . $error->getMessage());
            }
        }
    }

    private function checkPlusLicense(): void
    {
        try {
            $status = License::getStatus(checkCache: false);

            if ($status->hasNoLicense()) {
                $this->reportInfo('Koel Plus license status', 'Not available');

                return;
            }

            if ($status->isValid()) {
                $this->reportSuccess('Koel Plus license status', 'Active');
            } else {
                $this->reportError('Koel Plus license status', 'Invalid');
            }
        } catch (Throwable $e) {
            $this->collectError($e);
            $this->reportWarning('Cannot check for Koel Plus license status');
        }
    }

    private function checkScheduler(): void
    {
        $crontab = new CrontabRepository(new CrontabAdapter());

        if (InstallSchedulerCommand::schedulerInstalled($crontab)) {
            $this->reportSuccess('Koel scheduler status', 'Installed');
        } else {
            $this->reportWarning('Koel scheduler status', 'Not installed');
        }
    }

    private function checkMailConfiguration(): void
    {
        if (!config('mail.default') || config('mail.default') === 'log') {
            $this->reportWarning('Mailer configuration', 'Not available');

            return;
        }

        $recipient = Str::uuid() . '@mailinator.com';

        try {
            Mail::raw('This is a test email.', static fn (Message $message) => $message->to($recipient));
            $this->reportSuccess('Mailer configuration');
        } catch (Throwable $e) {
            $this->collectError($e);
            $this->reportError('Mailer configuration');
        }
    }

    private function checkServiceIntegrations(): void
    {
        if (!LastfmService::enabled()) {
            $this->reportWarning('Last.fm integration', 'Not available');
        } else {
            /** @var LastfmConnector $connector */
            $connector = app(LastfmConnector::class);

            /** @var Artist $artist */
            $artist = Artist::factory()->make(['name' => 'Pink Floyd']);

            try {
                $dto = $connector->send(new GetArtistInfoRequest($artist))->dto();

                if (!$dto) {
                    throw new Exception('No data returned.');
                }

                $this->reportSuccess('Last.fm integration');
            } catch (Throwable $e) {
                $this->collectError($e);
                $this->reportError('Last.fm integration');
            }
        }

        if (!YouTubeService::enabled()) {
            $this->reportWarning('YouTube integration', 'Not available');
        } else {
            /** @var YouTubeConnector $connector */
            $connector = app(YouTubeConnector::class);

            /** @var Song $artist */
            $song = Song::factory()->forArtist(['name' => 'Pink Floyd'])->make(['title' => 'Comfortably Numb']); // @phpstan-ignore-line

            try {
                $object = $connector->send(new SearchVideosRequest($song))->object();

                if (object_get($object, 'error')) {
                    throw new Exception(object_get($object, 'error.message'));
                }

                $this->reportSuccess('YouTube integration');
            } catch (Throwable $e) {
                $this->collectError($e);
                $this->reportError('YouTube integration');
            }
        }

        if (!SpotifyService::enabled()) {
            $this->reportWarning('Spotify integration', 'Not available');
        } else {
            /** @var SpotifyService $service */
            $service = app(SpotifyService::class);
            Cache::forget(SpotifyClient::ACCESS_TOKEN_CACHE_KEY);

            try {
                /** @var Artist $artist */
                $artist = Artist::factory()->make([
                    'id' => 999,
                    'name' => 'Pink Floyd',
                ]);

                $image = $service->tryGetArtistImage($artist);

                if (!$image) {
                    throw new Exception('No result returned.');
                }

                $this->reportSuccess('Spotify integration');
            } catch (Throwable $e) {
                $this->collectError($e);
                $this->reportError('Spotify integration');
            }
        }
    }

    private function checkStreamingMethod(): void
    {
        $this->reportInfo('Streaming method', config('koel.streaming.method'));
    }

    private function checkPhpConfiguration(): void
    {
        $this->reportInfo('Max upload size', ini_get('upload_max_filesize'));
        $this->reportInfo('Max post size', ini_get('post_max_size'));
    }

    private function checkPhpExtensions(): void
    {
        $this->assert(
            condition: extension_loaded('zip'),
            success: 'PHP extension <info>zip</info> is loaded. Multi-file downloading is supported.',
            warning: 'PHP extension <info>zip</info> is not loaded. Multi-file downloading will not be available.',
        );

        // as "gd" and "SimpleXML" are both required in the composer.json file, we don't need to check for them
    }

    private function checkFFMpeg(): void
    {
        $ffmpegPath = config('koel.streaming.ffmpeg_path');

        if ($ffmpegPath) {
            $this->assert(
                condition: File::exists($ffmpegPath) && is_executable($ffmpegPath),
                success: "FFmpeg binary <info>$ffmpegPath</info> is executable.",
                warning: "FFmpeg binary <info>$ffmpegPath</info> does not exist or is not executable. "
                . 'Transcoding will not be available.',
            );
        } else {
            $this->reportWarning('FFmpeg path is not set. Transcoding will not be available.');
        }
    }

    private function checkApiHealth(): void
    {
        try {
            Http::get(config('app.url') . '/api/ping');
            $this->reportSuccess('API is healthy');
        } catch (Throwable $e) {
            $this->collectError($e);
            $this->reportError('API is healthy');
        }
    }

    private function checkFullTextSearch(): void
    {
        if (config('scout.driver') === 'tntsearch') {
            $this->assertDirectoryPermissions(base_path('storage/search-indexes'), 'TNT search index');

            return;
        }

        if (config('scout.driver') === 'algolia') {
            try {
                Song::search('foo')->raw();
                $this->reportSuccess('Full-text search (using Algolia)');
            } catch (Throwable $e) {
                $this->collectError($e);
                $this->reportError('Full-text search (using Algolia)');
            }
        }
    }

    private function checkDatabaseConnection(): void
    {
        try {
            User::query()->count('id');
            $this->reportSuccess('Checking database connection');
        } catch (Throwable $e) {
            $this->collectError($e);
            $this->reportError('Checking database connection');
        }
    }

    private function checkMediaStorage(): void
    {
        /** @var SongStorage $storage */
        $storage = app(SongStorage::class);
        $name = $storage->getStorageType()->value ?: 'local';

        if (!$storage->getStorageType()->supported()) {
            $this->reportError("Media storage driver <info>$name</info>", 'Not supported');

            return;
        }

        if ($storage->getStorageType() === SongStorageType::LOCAL && !Setting::get('media_path')) {
            $this->reportWarning('Media path', 'Not set');

            return;
        }

        try {
            $storage->testSetup();
            $this->reportSuccess("Media storage setup (<info>$name</info>)");
        } catch (Throwable $e) {
            $this->collectError($e);
            $this->reportError("Media storage setup (<info>$name</info>)");
        }
    }

    private function checkFrameworkDirectoryPermissions(): void
    {
        $this->assertDirectoryPermissions(base_path('storage/framework/sessions'), 'Session');
        $this->assertDirectoryPermissions(base_path('storage/framework/cache'), 'Cache');
        $this->assertDirectoryPermissions(base_path('storage/logs'), 'Log');
    }

    private function reportError(string $message, ?string $value = 'ERROR'): void
    {
        $this->components->twoColumnDetail($message, "<error>$value</error>");
    }

    private function reportSuccess(string $message, ?string $value = 'OK'): void
    {
        $this->components->twoColumnDetail($message, "<info>$value</info>");
    }

    private function reportWarning(string $message, ?string $second = 'WARNING'): void
    {
        $this->components->twoColumnDetail($message, "<comment>$second</comment>");
    }

    private function reportInfo(string $message, ?string $value = null): void
    {
        $this->components->twoColumnDetail($message, $value);
    }

    private function assertDirectoryPermissions(string $path, string $name): void
    {
        $this->assert(
            condition: File::isReadable($path) && File::isWritable($path),
            success: "$name directory <info>$path</info> is readable/writable.",
            error: "$name directory <info>$path</info> is not readable/writable.",
        );
    }

    private function assert(
        Closure|bool $condition,
        Closure|string|null $success = null,
        Closure|string|null $error = null,
        Closure|string|null $warning = null,
    ): void {
        $result = value($condition);

        if ($result) {
            $this->reportSuccess(value($success));

            return;
        }

        if ($error && $warning) {
            throw new InvalidArgumentException('Cannot have both error and warning.');
        }

        if ($error) {
            $this->reportError(value($error));
        } else {
            $this->reportWarning(value($warning));
        }
    }

    private function collectError(Throwable $e): void
    {
        $this->errors[] = $e;
    }
}
