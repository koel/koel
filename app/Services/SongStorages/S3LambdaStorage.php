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
use App\Services\ImageStorage;
use App\Values\UploadReference;

/**
 * The legacy storage implementation for Lambda and S3, to provide backward compatibility.
 * In this implementation, the songs are supposed to be uploaded to S3 directly.
 */
class S3LambdaStorage extends S3CompatibleStorage
{
    public function __construct(
        private readonly ImageStorage $imageStorage,
        private readonly SongRepository $songRepository,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    public function storeUploadedFile(string $uploadedFilePath, User $uploader): UploadReference
    {
        throw new MethodNotImplementedException('Lambda storage does not support uploading.');
    }

    public function undoUpload(UploadReference $reference): void
    {
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
        $user = $this->userRepository->getFirstAdminUser();
        $path = Song::getPathFromS3BucketAndKey($bucket, $key);
        $artist = Artist::getOrCreate($user, $artistName);

        $albumArtist = $albumArtistName && $albumArtistName !== $artistName
            ? Artist::getOrCreate($user, $albumArtistName)
            : $artist;

        $album = Album::getOrCreate($albumArtist, $albumName);

        if ($cover) {
            $this->imageStorage->storeAlbumCover($album, base64_decode($cover['data'], true));
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

    public function delete(string $location, bool $backup = false): void
    {
        throw new MethodNotImplementedException('Lambda storage does not support deleting from filesystem.');
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::S3_LAMBDA;
    }
}
