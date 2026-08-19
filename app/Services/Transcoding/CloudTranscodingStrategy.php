<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Enums\TranscodeCodec;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\SongStorages\CloudStorage;
use App\Services\SongStorages\CloudStorageFactory;
use Illuminate\Support\Facades\File;
use Throwable;

class CloudTranscodingStrategy extends TranscodingStrategy
{
    public function getExistingTranscodeLocation(Song $song, int $bitRate): ?string
    {
        $location = parent::getExistingTranscodeLocation($song, $bitRate);

        if (!$location) {
            return null;
        }

        $storage = CloudStorageFactory::make($song->storage);

        return $storage->fileExists($location) ? $storage->getPresignedUrl($location) : null;
    }

    public function publishCompletedTranscode(
        Song $song,
        string $temporaryPath,
        int $bitRate,
        TranscodeCodec $codec,
    ): string {
        $existingLocation = $this->getExistingTranscodeLocation($song, $bitRate);

        if ($existingLocation) {
            return $existingLocation;
        }

        $storage = CloudStorageFactory::make($song->storage);
        $key = sprintf('transcodes/%d/%s.%s', $bitRate, Ulid::generate(), $codec->extension());

        try {
            $storage->uploadToStorage($key, $temporaryPath);

            $this->createOrUpdateTranscode(
                $song,
                $key,
                $bitRate,
                $codec,
                File::hash($temporaryPath),
                File::size($temporaryPath),
            );
        } catch (Throwable $e) {
            self::deleteFailedUpload($storage, $key);

            throw $e;
        }

        return $storage->getPresignedUrl($key);
    }

    protected function createTranscodeLocation(Song $song, int $bitRate, TranscodeCodec $codec): string
    {
        $storage = CloudStorageFactory::make($song->storage);
        $transcode = $this->createTranscode($storage, $song, $bitRate, $codec);

        return $storage->getPresignedUrl($transcode->location);
    }

    /**
     * Create a new transcode for the given song at the specified bit rate by performing the following steps:
     * 1. Transcode the song to the specified bit rate and store it temporarily.
     * 2. Upload the transcoded file back to the cloud storage.
     * 3. Store the transcode record in the database.
     * 4. Delete the temporary file.
     */
    private function createTranscode(CloudStorage $storage, Song $song, int $bitRate, TranscodeCodec $codec): Transcode
    {
        $tmpDestination = artifact_path(sprintf('tmp/%s.%s', Ulid::generate(), $codec->extension()));

        try {
            $this->transcoder->transcode(
                $storage->getPresignedUrl($song->storage_metadata->getPath()),
                $tmpDestination,
                $bitRate,
                $codec,
            );

            $key = sprintf('transcodes/%d/%s.%s', $bitRate, Ulid::generate(), $codec->extension());

            try {
                $storage->uploadToStorage($key, $tmpDestination);

                return $this->createOrUpdateTranscode(
                    $song,
                    $key,
                    $bitRate,
                    $codec,
                    File::hash($tmpDestination),
                    File::size($tmpDestination),
                );
            } catch (Throwable $e) {
                self::deleteFailedUpload($storage, $key);

                throw $e;
            }
        } finally {
            File::delete($tmpDestination);
        }
    }

    public function deleteTranscodeFile(string $location, SongStorageType $storageType): void
    {
        CloudStorageFactory::make($storageType)->deleteFileWithKey(key: $location, backup: false);
    }

    private static function deleteFailedUpload(CloudStorage $storage, string $key): void
    {
        try {
            $storage->deleteFileWithKey($key, backup: false);
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }
    }
}
