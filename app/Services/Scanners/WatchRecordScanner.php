<?php

namespace App\Services\Scanners;

use App\Models\Song;
use App\Values\Scanning\ScanConfiguration;
use App\Values\WatchRecord\Contracts\WatchRecordInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class WatchRecordScanner extends Scanner
{
    public function scan(WatchRecordInterface $record, ScanConfiguration $config): void
    {
        self::setSystemRequirements();

        Log::info("Scanning watch record: '{$record->getPath()}'");

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

    private function handleDeletedFileRecord(string $path): void
    {
        $result = $this->songRepository->findOneByPath($path)?->delete();

        Log::info($result === null ? "$path deleted." : "$path doesn't exist in our database--skipping.");
    }

    private function handleNewOrModifiedFileRecord(string $path, ScanConfiguration $config): void
    {
        try {
            $this->songService->createOrUpdateSongFromScan($this->fileScanner->scan($path), $config);
            Log::info("Scanned $path");
        } catch (Throwable $e) {
            Log::warning("Failed to scan $path.", ['error' => $e]);
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
        foreach ($this->gatherFiles($path) as $file) {
            $this->handleIndividualFile($file->getPathname(), $config);
        }

        Log::info("Scanned all song(s) under $path");
    }
}
