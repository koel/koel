<?php

namespace App\Services;

use App\Events\LibraryChanged;
use App\Events\MediaScanCompleted;
use App\Libraries\WatchRecord\WatchRecordInterface;
use App\Models\Song;
use App\Repositories\SettingRepository;
use App\Repositories\SongRepository;
use App\Values\ScanConfiguration;
use App\Values\ScanResult;
use App\Values\ScanResultCollection;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Throwable;

class MediaScanner
{
    /** @var array<array-key, callable> */
    private array $events = [];

    public function __construct(
        private SettingRepository $settingRepository,
        private SongRepository $songRepository,
        private FileScanner $fileScanner,
        private Finder $finder,
        private LoggerInterface $logger
    ) {
    }

    public function scan(ScanConfiguration $config): ScanResultCollection
    {
        /** @var string $mediaPath */
        $mediaPath = $this->settingRepository->getByKey('media_path');

        $this->setSystemRequirements();

        $results = ScanResultCollection::create();
        $songPaths = $this->gatherFiles($mediaPath);

        if (isset($this->events['paths-gathered'])) {
            $this->events['paths-gathered']($songPaths);
        }

        foreach ($songPaths as $path) {
            try {
                $result = $this->fileScanner->setFile($path)->scan($config);
            } catch (Throwable) {
                $result = ScanResult::error($path, 'Possible invalid file');
            }

            $results->add($result);

            if (isset($this->events['progress'])) {
                $this->events['progress']($result);
            }
        }

        event(new MediaScanCompleted($results));

        // Trigger LibraryChanged, so that PruneLibrary handler is fired to prune the lib.
        event(new LibraryChanged());

        return $results;
    }

    /**
     * Gather all applicable files in a given directory.
     *
     * @param string $path The directory's full path
     *
     * @return array<SplFileInfo>
     */
    private function gatherFiles(string $path): array
    {
        return iterator_to_array(
            $this->finder->create()
                ->ignoreUnreadableDirs()
                ->ignoreDotFiles((bool)config('koel.ignore_dot_files')) // https://github.com/koel/koel/issues/450
                ->files()
                ->followLinks()
                ->name('/\.(mp3|wav|ogg|m4a|flac|opus)$/i')
                ->in($path)
        );
    }

    public function scanWatchRecord(WatchRecordInterface $record, ScanConfiguration $config): void
    {
        $this->logger->info("New watch record received: '{$record->getPath()}'");

        if ($record->isFile()) {
            $this->scanFileRecord($record, $config);
        } else {
            $this->scanDirectoryRecord($record, $config);
        }
    }

    private function scanFileRecord(WatchRecordInterface $record, ScanConfiguration $config): void
    {
        $path = $record->getPath();
        $this->logger->info("'$path' is a file.");

        if ($record->isDeleted()) {
            $this->handleDeletedFileRecord($path);
        } elseif ($record->isNewOrModified()) {
            $this->handleNewOrModifiedFileRecord($path, $config);
        }
    }

    private function scanDirectoryRecord(WatchRecordInterface $record, ScanConfiguration $config): void
    {
        $path = $record->getPath();
        $this->logger->info("'$path' is a directory.");

        if ($record->isDeleted()) {
            $this->handleDeletedDirectoryRecord($path);
        } elseif ($record->isNewOrModified()) {
            $this->handleNewOrModifiedDirectoryRecord($path, $config);
        }
    }

    private function setSystemRequirements(): void
    {
        if (!app()->runningInConsole()) {
            set_time_limit(config('koel.sync.timeout'));
        }

        if (config('koel.memory_limit')) {
            ini_set('memory_limit', config('koel.memory_limit') . 'M');
        }
    }

    private function handleDeletedFileRecord(string $path): void
    {
        $song = $this->songRepository->getOneByPath($path);

        if ($song) {
            $song->delete();
            $this->logger->info("$path deleted.");
            event(new LibraryChanged());
        } else {
            $this->logger->info("$path doesn't exist in our database--skipping.");
        }
    }

    private function handleNewOrModifiedFileRecord(string $path, ScanConfiguration $config): void
    {
        $result = $this->fileScanner->setFile($path)->scan($config);

        if ($result->isSuccess()) {
            $this->logger->info("Scanned $path");
        } else {
            $this->logger->info("Failed to scan $path. Maybe an invalid file?");
        }

        event(new LibraryChanged());
    }

    private function handleDeletedDirectoryRecord(string $path): void
    {
        $count = Song::query()->inDirectory($path)->delete();

        if ($count) {
            $this->logger->info("Deleted $count song(s) under $path");

            event(new LibraryChanged());
        } else {
            $this->logger->info("$path is empty--no action needed.");
        }
    }

    private function handleNewOrModifiedDirectoryRecord(string $path, ScanConfiguration $config): void
    {
        $scanResults = ScanResultCollection::create();

        foreach ($this->gatherFiles($path) as $file) {
            try {
                $scanResults->add($this->fileScanner->setFile($file)->scan($config));
            } catch (Throwable) {
                $scanResults->add(ScanResult::error($file->getRealPath(), 'Possible invalid file'));
            }
        }

        $this->logger->info("Scanned all song(s) under $path");

        event(new MediaScanCompleted($scanResults));
        event(new LibraryChanged());
    }

    public function on(string $event, callable $callback): void
    {
        $this->events[$event] = $callback;
    }
}
