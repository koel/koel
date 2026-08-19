<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Enums\TranscodeCodec;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Services\SongStorages\SftpStorage;
use Illuminate\Support\Facades\File;
use Throwable;
use Webmozart\Assert\Assert;

class SftpTranscodingStrategy extends TranscodingStrategy
{
    protected function createTranscodeLocation(Song $song, int $bitRate, TranscodeCodec $codec): string
    {
        /** @var SftpStorage $storage */
        $storage = app(SftpStorage::class);
        $tmpSource = $storage->copyToLocal($song->storage_metadata->getPath());

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
