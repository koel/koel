<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Services\SongStorages\SftpStorage;
use Illuminate\Support\Facades\File;
use Throwable;
use Webmozart\Assert\Assert;

class SftpTranscodingStrategy extends TranscodingStrategy
{
    public function getTranscodeLocation(Song $song, int $bitRate): string
    {
        $transcode = $this->findTranscode($song, $bitRate);

        if ($transcode?->isValid()) {
            return $transcode->location;
        }

        // If a transcode record exists, but is not valid (i.e., checksum failed), delete the associated file.
        if ($transcode) {
            File::delete($transcode->location);
        }

        /** @var SftpStorage $storage */
        $storage = app(SftpStorage::class);
        $tmpSource = $storage->copyToLocal($song->storage_metadata->getPath());

        $codec = $this->transcoder->preferredCodec();
        $destination = artifact_path(sprintf('transcodes/%d/%s.%s', $bitRate, Ulid::generate(), $codec->extension()));

        try {
            $this->transcodeAndUpsert($song, $tmpSource, $destination, $bitRate, $codec);
        } catch (Throwable $e) {
            File::delete($destination);

            throw $e;
        } finally {
            File::delete($tmpSource);
        }

        return $destination;
    }

    public function deleteTranscodeFile(string $location, SongStorageType $storageType): void
    {
        Assert::eq($storageType, SongStorageType::SFTP);

        File::delete($location);
    }
}
