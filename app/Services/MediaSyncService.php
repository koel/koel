<?php

namespace App\Services;

use App\Console\Commands\SyncCommand;
use App\Events\LibraryChanged;
use App\Events\MediaSyncCompleted;
use App\Libraries\WatchRecord\WatchRecordInterface;
use App\Models\Setting;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Values\SyncResult;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class MediaSyncService
{
    public function __construct(
        private SongRepository $songRepository,
        private FileSynchronizer $fileSynchronizer,
        private Finder $finder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string> $excludes The tags to exclude.
     * Only taken into account for existing records.
     * New records will have all tags synced in regardless.
     * @param bool $force Whether to force syncing even unchanged files
     * @param SyncCommand $syncCommand The SyncMedia command object, to log to console if executed by artisan
     */
    public function sync(
        ?string $mediaPath = null,
        array $excludes = [],
        bool $force = false,
        ?SyncCommand $syncCommand = null
    ): void {
        $this->setSystemRequirements();

        $syncResult = SyncResult::init();
        $songPaths = $this->gatherFiles($mediaPath ?: Setting::get('media_path'));
        $syncCommand?->createProgressBar(count($songPaths));

        foreach ($songPaths as $path) {
            $result = $this->fileSynchronizer->setFile($path)->sync($excludes, $force);

            switch ($result) {
                case FileSynchronizer::SYNC_RESULT_SUCCESS:
                    $syncResult->success->add($path);
                    break;

                case FileSynchronizer::SYNC_RESULT_UNMODIFIED:
                    $syncResult->unmodified->add($path);
                    break;

                default:
                    $syncResult->bad->add($path);
                    break;
            }

            if ($syncCommand) {
                $syncCommand->advanceProgressBar();
                $syncCommand->logSyncStatusToConsole($path, $result, $this->fileSynchronizer->getSyncError());
            }
        }

        event(new MediaSyncCompleted($syncResult));

        // Trigger LibraryChanged, so that PruneLibrary handler is fired to prune the lib.
        event(new LibraryChanged());
    }

    /**
     * Gather all applicable files in a given directory.
     *
     * @param string $path The directory's full path
     *
     * @return array<SplFileInfo>
     */
    public function gatherFiles(string $path): array
    {
        return iterator_to_array(
            $this->finder->create()
                ->ignoreUnreadableDirs()
                ->ignoreDotFiles((bool) config('koel.ignore_dot_files')) // https://github.com/koel/koel/issues/450
                ->files()
                ->followLinks()
                ->name('/\.(mp3|ogg|m4a|flac)$/i')
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

        if ($result === FileSynchronizer::SYNC_RESULT_SUCCESS) {
            $this->logger->info("Synchronized $path");
        } else {
            $this->logger->info("Failed to synchronized $path. Maybe an invalid file?");
        }

        event(new LibraryChanged());
    }

    private function handleDeletedDirectoryRecord(string $path): void
    {
        $count = Song::inDirectory($path)->delete();

        if ($count) {
            $this->logger->info("Deleted $count song(s) under $path");

            event(new LibraryChanged());
        } else {
            $this->logger->info("$path is empty--no action needed.");
        }
    }

    private function handleNewOrModifiedDirectoryRecord(string $path): void
    {
        foreach ($this->gatherFiles($path) as $file) {
            $this->fileSynchronizer->setFile($file)->sync();
        }

        $this->logger->info("Synced all song(s) under $path");

        event(new LibraryChanged());
    }
}
