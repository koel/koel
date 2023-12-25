<?php

namespace App\Services;

use App\Events\LibraryChanged;
use App\Events\MediaSyncCompleted;
use App\Libraries\WatchRecord\WatchRecordInterface;
use App\Models\Song;
use App\Repositories\SettingRepository;
use App\Repositories\SongRepository;
use App\Values\SyncResult;
use App\Values\SyncResultCollection;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Throwable;

class MediaSyncService
{
    /** @var array<array-key, callable> */
    private array $events = [];

    public function __construct(
        private SettingRepository $settingRepository,
        private SongRepository $songRepository,
        private FileSynchronizer $fileSynchronizer,
        private Finder $finder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string> $ignores The tags to ignore.
     * Only taken into account for existing records.
     * New records will have all tags synced in regardless.
     * @param bool $force Whether to force syncing even unchanged files
     */
    public function sync(array $ignores = [], bool $force = false): SyncResultCollection
    {
        /** @var string $mediaPath */
        $mediaPath = $this->settingRepository->getByKey('media_path');

        $this->setSystemRequirements();

        $results = SyncResultCollection::create();
        $songPaths = $this->gatherFiles($mediaPath);

        if (isset($this->events['paths-gathered'])) {
            $this->events['paths-gathered']($songPaths);
        }

        foreach ($songPaths as $path) {
            try {
                $result = $this->fileSynchronizer->setFile($path)->sync($ignores, $force);
            } catch (Throwable) {
                $result = SyncResult::error($path, 'Possible invalid file');
            }

            $results->add($result);

            if (isset($this->events['progress'])) {
                $this->events['progress']($result);
            }
        }

        event(new MediaSyncCompleted($results));

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
                ->ignoreDotFiles((bool) config('koel.ignore_dot_files')) // https://github.com/koel/koel/issues/450
                ->files()
                ->followLinks()
                ->name('/\.(mp3|wav|ogg|m4a|flac|opus)$/i')
                ->in($path)
        );
    }

    public function syncByWatchRecord(WatchRecordInterface $record): void
    {
        $this->logger->info("New watch record received: '{$record->getPath()}'");
        $record->isFile() ? $this->syncFileRecord($record) : $this->syncDirectoryRecord($record);
    }

    private function syncFileRecord(WatchRecordInterface $record): void
    {
        $path = $record->getPath();
        $this->logger->info("'$path' is a file.");

        if ($record->isDeleted()) {
            $this->handleDeletedFileRecord($path);
        } elseif ($record->isNewOrModified()) {
            $this->handleNewOrModifiedFileRecord($path);
        }
    }

    private function syncDirectoryRecord(WatchRecordInterface $record): void
    {
        $path = $record->getPath();
        $this->logger->info("'$path' is a directory.");

        if ($record->isDeleted()) {
            $this->handleDeletedDirectoryRecord($path);
        } elseif ($record->isNewOrModified()) {
            $this->handleNewOrModifiedDirectoryRecord($path);
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

    private function handleNewOrModifiedFileRecord(string $path): void
    {
        $result = $this->fileSynchronizer->setFile($path)->sync();

        if ($result->isSuccess()) {
            $this->logger->info("Synchronized $path");
        } else {
            $this->logger->info("Failed to synchronized $path. Maybe an invalid file?");
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

    private function handleNewOrModifiedDirectoryRecord(string $path): void
    {
        $syncResults = SyncResultCollection::create();

        foreach ($this->gatherFiles($path) as $file) {
            try {
                $syncResults->add($this->fileSynchronizer->setFile($file)->sync());
            } catch (Throwable) {
                $syncResults->add(SyncResult::error($file->getRealPath(), 'Possible invalid file'));
            }
        }

        $this->logger->info("Synced all song(s) under $path");

        event(new MediaSyncCompleted($syncResults));
        event(new LibraryChanged());
    }

    public function on(string $event, callable $callback): void
    {
        $this->events[$event] = $callback;
    }
}
