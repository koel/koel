<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Exceptions\MethodNotImplementedException;
use App\Exceptions\SongPathNotFoundException;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Repositories\UserRepository;
use App\Services\MediaMetadataService;
use Illuminate\Http\UploadedFile;

/**
 * The legacy storage implementation for Lambda and S3, to provide backward compatibility.
 * In this implementation, the songs are supposed to be uploaded to S3 directly.
 */
final class S3LambdaStorage extends S3CompatibleStorage
{
    public function __construct( // @phpcs:ignore
        private readonly MediaMetadataService $mediaMetadataService,
        private readonly SongRepository $songRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    public function storeUploadedFile(UploadedFile $file, User $uploader): Song
    {
        $this->assertSupported();

        throw new MethodNotImplementedException('Lambda storage does not support uploading.');
    }

    public function createSongEntry(
        string $bucket,
        string $key,
        string $artistName,
        string $albumName,
        string $albumArtistName,
        ?array $cover,
        string $title,
        float $duration,
        int $track,
        string $lyrics
    ): Song {
        $user = $this->userRepository->getDefaultAdminUser();
        $path = Song::getPathFromS3BucketAndKey($bucket, $key);
        $artist = Artist::getOrCreate($artistName);

        $albumArtist = $albumArtistName && $albumArtistName !== $artistName
            ? Artist::getOrCreate($albumArtistName)
            : $artist;

        $album = Album::getOrCreate($albumArtist, $albumName);

        if ($cover) {
            $this->mediaMetadataService->writeAlbumCover($album, base64_decode($cover['data'], true));
        }

        return Song::query()->updateOrCreate(['path' => $path], [
            'album_id' => $album->id,
            'artist_id' => $artist->id,
            'title' => $title,
            'length' => $duration,
            'track' => $track,
            'lyrics' => $lyrics,
            'mtime' => time(),
            'owner_id' => $user->id,
            'is_public' => true,
            'storage' => SongStorageType::S3_LAMBDA,
        ]);
    }

    public function deleteSongEntry(string $bucket, string $key): void
    {
        $path = Song::getPathFromS3BucketAndKey($bucket, $key);
        $song = $this->songRepository->findOneByPath($path);

        throw_unless((bool) $song, SongPathNotFoundException::create($path));

        $song->delete();
    }

    public function delete(Song $song, bool $backup = false): void
    {
        throw new MethodNotImplementedException('Lambda storage does not support deleting from filesystem.');
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::S3_LAMBDA;
    }
}
