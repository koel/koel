<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Models\Transcode;
use App\Repositories\TranscodeRepository;
use App\Values\Transcoding\PreviewSection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

abstract class TranscodingStrategy
{
    private const int TRANSCODE_LOCK_SECONDS = 600;
    private const int TRANSCODE_LOCK_WAIT_SECONDS = 60;
    private const int PREVIEW_BIT_RATE = 64;

    public function __construct(
        protected TranscodeRepository $transcodeRepository,
        protected Transcoder $transcoder,
    ) {}

    protected function findTranscode(Song $song, int $bitRate, ?PreviewSection $previewSection): ?Transcode
    {
        return $this->transcodeRepository->findFirstWhere([
            'song_id' => $song->id,
            'bit_rate' => $bitRate,
            'is_preview' => $previewSection !== null,
        ]);
    }

    protected function createOrUpdateTranscode(
        Song $song,
        string $locationOrCloudKey,
        int $bitRate,
        ?PreviewSection $previewSection,
        string $hash,
        int $fileSize,
    ): Transcode {
        Transcode::query()->upsert(
            values: [
                'song_id' => $song->id,
                'location' => $locationOrCloudKey,
                'bit_rate' => $bitRate,
                'is_preview' => $previewSection !== null,
                'hash' => $hash,
                'file_size' => $fileSize,
            ],
            uniqueBy: ['song_id', 'bit_rate', 'is_preview'],
            update: ['location', 'hash', 'file_size'],
        );

        return $this->findTranscode($song, $bitRate, $previewSection); // @phpstan-ignore-line
    }

    protected function transcodeAndUpsert(
        Song $song,
        string $tmpSource,
        string $destination,
        int $bitRate,
        ?PreviewSection $previewSection,
    ): void {
        $this->transcoder->transcode($tmpSource, $destination, $bitRate, $previewSection);

        $this->createOrUpdateTranscode(
            $song,
            $destination,
            $bitRate,
            $previewSection,
            File::hash($destination),
            File::size($destination),
        );
    }

    /**
     * @param string|null $localSourcePath A copy of the song already on this machine, used instead of fetching one
     */
    public function getTranscodeLocation(Song $song, int $bitRate, ?string $localSourcePath = null): string
    {
        return $this->findOrCreateLocationWithLock(
            cache_key('transcode', $song->id, $bitRate),
            $song,
            $bitRate,
            $localSourcePath,
        );
    }

    /**
     * @param string|null $localSourcePath A copy of the song already on this machine, used instead of fetching one
     */
    public function getPreviewLocation(Song $song, ?string $localSourcePath = null): string
    {
        return $this->findOrCreateLocationWithLock(
            cache_key('preview', $song->id),
            $song,
            self::PREVIEW_BIT_RATE,
            $localSourcePath,
            PreviewSection::make($song->length),
        );
    }

    private function findOrCreateLocationWithLock(
        string $lockKey,
        Song $song,
        int $bitRate,
        ?string $localSourcePath,
        ?PreviewSection $previewSection = null,
    ): string {
        return Cache::lock(
            $lockKey,
            self::TRANSCODE_LOCK_SECONDS,
        )->block(self::TRANSCODE_LOCK_WAIT_SECONDS, fn (): string => $this->findOrCreateTranscodeLocation(
            $song,
            $bitRate,
            $localSourcePath,
            $previewSection,
        ));
    }

    abstract protected function findOrCreateTranscodeLocation(
        Song $song,
        int $bitRate,
        ?string $localSourcePath,
        ?PreviewSection $previewSection,
    ): string;

    abstract public function deleteTranscodeFile(string $location, SongStorageType $storageType): void;
}
