<?php

namespace App\Services\Scanner;

use App\Enums\ScanEvent;
use App\Events\LibraryChanged;
use App\Events\MediaScanCompleted;
use App\Models\Setting;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Values\ScanConfiguration;
use App\Values\ScanResultCollection;
use App\Values\WatchRecord\Contracts\WatchRecordInterface;
use Illuminate\Support\Facades\Log;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class MediaScanner
{
    /** @var array<string, callable> */
    private array $events = [];

    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly FileScanner $fileScanner,
        private readonly Finder $finder
    ) {
    }

    public function scan(ScanConfiguration $config): ScanResultCollection
    {
        $this->setSystemRequirements();

        $songPaths = $this->gatherFiles((string) Setting::get('media_path'));

        if (isset($this->events[ScanEvent::PATHS_GATHERED->name])) {
            $this->events[ScanEvent::PATHS_GATHERED->name]($songPaths);
        }

        $results = ScanResultCollection::create();

        foreach ($songPaths as $path) {
            $result = $this->fileScanner->setFile($path)->scan($config);
            $results->add($result);

            if (isset($this->events[ScanEvent::SCAN_PROGRESS->name])) {
                $this->events[ScanEvent::SCAN_PROGRESS->name]($result);
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
        $nameRegex = '/\.(' . implode('|', config('koel.streaming.supported_formats')) . ')$/i';

        return iterator_to_array(
            $this->finder::create()
                ->ignoreUnreadableDirs()
                ->ignoreDotFiles((bool) config('koel.ignore_dot_files')) // https://github.com/koel/koel/issues/450
                ->files()
                ->followLinks()
                ->name($nameRegex)
                ->in($path)
        );
    }

    public function scanWatchRecord(WatchRecordInterface $record, ScanConfiguration $config): void
    {
        Log::info("New watch record received: '{$record->getPath()}'");

        if ($record->isFile()) {
            $this->scanFileRecord($record, $config);
        } else {
            $this->scanDirectoryRecord($record, $config);
        }
    }

    private function scanFileRecord(WatchRecordInterface $record, ScanConfiguration $config): void
    {
        $path = $record->getPath();
        Log::info("'$path' is a file.");

        if ($record->isDeleted()) {
            $this->handleDeletedFileRecord($path);
        } elseif ($record->isNewOrModified()) {
            $this->handleNewOrModifiedFileRecord($path, $config);
        }
    }

    private function scanDirectoryRecord(WatchRecordInterface $record, ScanConfiguration $config): void
    {
        $path = $record->getPath();
        Log::info("'$path' is a directory.");

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
        $song = $this->songRepository->findOneByPath($path);

        if ($song) {
            $song->delete();
            Log::info("$path deleted.");
        } else {
            Log::info("$path doesn't exist in our database--skipping.");
        }
    }

    private function handleNewOrModifiedFileRecord(string $path, ScanConfiguration $config): void
    {
        if ($this->fileScanner->setFile($path)->scan($config)->isSuccess()) {
            Log::info("Scanned $path");
        } else {
            Log::info("Failed to scan $path. Maybe an invalid file?");
        }
    }

    private function handleDeletedDirectoryRecord(string $path): void
    {
        $count = Song::query()->inDirectory($path)->delete();

        if ($count) {
            Log::info("Deleted $count song(s) under $path");
        } else {
            Log::info("$path is empty--no action needed.");
        }
    }

    private function handleNewOrModifiedDirectoryRecord(string $path, ScanConfiguration $config): void
    {
        $scanResults = ScanResultCollection::create();

        foreach ($this->gatherFiles($path) as $file) {
            $scanResults->add($this->fileScanner->setFile($file)->scan($config));
        }

        Log::info("Scanned all song(s) under $path");

        event(new MediaScanCompleted($scanResults));
    }

    public function on(ScanEvent $event, callable $callback): void
    {
        $this->events[$event->name] = $callback;
    }
}
