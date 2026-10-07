<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Repositories\TranscodeRepository;
use App\Services\SongStorages\RemoteDiskStorage;
use Illuminate\Support\Facades\File;
use Throwable;
use Webmozart\Assert\Assert;

class RemoteDiskTranscodingStrategy extends TranscodingStrategy
{
    public function __construct(
        TranscodeRepository $transcodeRepository,
        Transcoder $transcoder,
        private readonly RemoteDiskStorage $storage,
    ) {
        parent::__construct($transcodeRepository, $transcoder);
    }

    protected function findOrCreateTranscodeLocation(Song $song, int $bitRate, ?string $localSourcePath): string
    {
        $transcode = $this->findTranscodeBySongAndBitRate($song, $bitRate);

        if ($transcode?->isValid()) {
            return $transcode->location;
        }

        // If a transcode record exists, but is not valid (i.e., checksum failed), delete the associated file.
        if ($transcode) {
            File::delete($transcode->location);
        }

        $downloadedSource = $localSourcePath ? null : $this->storage->copyToLocal($song->storage_metadata->getPath());

        $destination = artifact_path(sprintf('transcodes/%d/%s.m4a', $bitRate, Ulid::generate()));

        try {
            $this->transcodeAndUpsert($song, $localSourcePath ?? $downloadedSource, $destination, $bitRate);
        } catch (Throwable $e) {
            File::delete($destination);

            throw $e;
        } finally {
            if ($downloadedSource) {
                File::delete($downloadedSource);
            }
        }

        return $destination;
    }

    public function deleteTranscodeFile(string $location, SongStorageType $storageType): void
    {
        Assert::eq($storageType, $this->storage->getStorageType());

        File::delete($location);
    }
}
