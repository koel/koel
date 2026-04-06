<?php

namespace App\Services\Concerns;

use App\Models\Song;
use App\Models\User;
use App\Services\Scanners\FileScanner;
use App\Services\SongService;
use App\Services\SongStorages\SongStorage;
use App\Values\Scanning\ScanConfiguration;

trait ScansAndStoresSong
{
    private function scanAndStore(
        string $localFilePath,
        string $storageLocation,
        User $owner,
        FileScanner $scanner,
        SongService $songService,
        SongStorage $storage,
    ): Song {
        $config = ScanConfiguration::make(
            owner: $owner,
            makePublic: $owner->preferences->makeUploadsPublic,
            extractFolderStructure: $storage->getStorageType()->supportsFolderStructureExtraction(),
        );

        $song = $songService->createOrUpdateSongFromScan($scanner->scan($localFilePath), $config);

        if ($song->path !== $storageLocation || $song->storage !== $storage->getStorageType()) {
            $song->update([
                'path' => $storageLocation,
                'storage' => $storage->getStorageType(),
            ]);
        }

        return $song;
    }
}
