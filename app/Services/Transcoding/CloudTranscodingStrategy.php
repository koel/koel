<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\SongStorages\CloudStorage;
use App\Services\SongStorages\CloudStorageFactory;
use Illuminate\Support\Facades\File;

class CloudTranscodingStrategy extends TranscodingStrategy
{
    public function getTranscodeLocation(Song $song, int $bitRate): string
    {
        $storage = CloudStorageFactory::make($song->storage);

        $transcode = $this->findTranscodeBySongAndBitRate($song, $bitRate)
            ?? $this->createTranscode($storage, $song, $bitRate);

        return $storage->getPresignedUrl($transcode->location);
    }

    /**
     * Create a new transcode for the given song at the specified bit rate by performing the following steps:
     * 1. Transcode the song to the specified bit rate and storing it temporarily.
     * 2. Upload the transcoded file back to the cloud storage.
     * 3. Store the transcode record in the database.
     * 4. Delete the temporary file.
     */
    private function createTranscode(CloudStorage $storage, Song $song, int $bitRate): Transcode
    {
        $tmpDestination = artifact_path(sprintf('tmp/%s.m4a', Ulid::generate()));

        $this->transcoder->transcode(
            $storage->getPresignedUrl($song->storage_metadata->getPath()),
            $tmpDestination,
            $bitRate,
        );

        $key = sprintf('transcodes/%d/%s.m4a', $bitRate, Ulid::generate());

        try {
            $storage->uploadToStorage($key, $tmpDestination);

            return $this->createOrUpdateTranscode($song, $key, $bitRate, File::hash($tmpDestination));
        } finally {
            File::delete($tmpDestination);
        }
    }

    public function deleteTranscodeFile(string $location, SongStorageType $storageType): void
    {
        CloudStorageFactory::make($storageType)->deleteFileWithKey(key: $location, backup: false);
    }
}
