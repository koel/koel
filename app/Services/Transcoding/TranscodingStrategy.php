<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Enums\TranscodeCodec;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Models\Transcode;
use App\Repositories\TranscodeRepository;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

abstract class TranscodingStrategy
{
    public function __construct(
        protected TranscodeRepository $transcodeRepository,
        protected Transcoder $transcoder,
        private readonly OpusTranscodeCoordinator $opusTranscodeCoordinator,
    ) {}

    protected function findTranscode(Song $song, int $bitRate): ?Transcode
    {
        return $this->transcodeRepository->findFirstWhere([
            'song_id' => $song->id,
            'bit_rate' => $bitRate,
        ]);
    }

    public function getExistingTranscodeLocation(Song $song, int $bitRate): ?string
    {
        $transcode = $this->findTranscode($song, $bitRate);

        if (!$transcode) {
            return null;
        }

        if ($transcode->isValid()) {
            return $transcode->location;
        }

        File::delete($transcode->location);

        return null;
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

        $destination = artifact_path(sprintf('transcodes/%d/%s.%s', $bitRate, Ulid::generate(), $codec->extension()));

        throw_unless(File::move($temporaryPath, $destination), RuntimeException::class, 'Failed to publish transcode.');

        try {
            $this->createOrUpdateTranscode(
                $song,
                $destination,
                $bitRate,
                $codec,
                File::hash($destination),
                File::size($destination),
            );
        } catch (Throwable $e) {
            File::delete($destination);

            throw $e;
        }

        return $destination;
    }

    protected function createOrUpdateTranscode(
        Song $song,
        string $locationOrCloudKey,
        int $bitRate,
        TranscodeCodec $codec,
        string $hash,
        int $fileSize,
    ): Transcode {
        Transcode::query()->upsert(
            values: [
                'song_id' => $song->id,
                'location' => $locationOrCloudKey,
                'bit_rate' => $bitRate,
                'codec' => $codec,
                'hash' => $hash,
                'file_size' => $fileSize,
            ],
            uniqueBy: ['song_id', 'bit_rate'],
            update: ['location', 'codec', 'hash', 'file_size'],
        );

        return $this->findTranscode($song, $bitRate); // @phpstan-ignore-line
    }

    protected function transcodeAndUpsert(
        Song $song,
        string $tmpSource,
        string $destination,
        int $bitRate,
        TranscodeCodec $codec,
    ): void {
        $this->transcoder->transcode($tmpSource, $destination, $bitRate, $codec);

        $this->createOrUpdateTranscode(
            $song,
            $destination,
            $bitRate,
            $codec,
            File::hash($destination),
            File::size($destination),
        );
    }

    public function getTranscodeLocation(Song $song, int $bitRate): string
    {
        $existingLocation = $this->getExistingTranscodeLocation($song, $bitRate);

        if ($existingLocation) {
            return $existingLocation;
        }

        $codec = $this->transcoder->preferredCodec();

        if ($codec !== TranscodeCodec::OPUS) {
            return $this->createTranscodeLocation($song, $bitRate, $codec);
        }

        return $this->opusTranscodeCoordinator->runExclusively(
            $song,
            $bitRate,
            fn (): string => (
                $this->getExistingTranscodeLocation($song, $bitRate) ?? $this->createTranscodeLocation(
                    $song,
                    $bitRate,
                    $codec,
                )
            ),
        );
    }

    abstract protected function createTranscodeLocation(Song $song, int $bitRate, TranscodeCodec $codec): string;

    abstract public function deleteTranscodeFile(string $location, SongStorageType $storageType): void;
}
