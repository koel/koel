<?php

namespace App\Services;

use App\Events\LibraryChanged;
use App\Exceptions\SongPathNotFoundException;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\SongRepository;
use Aws\S3\S3ClientInterface;
use Illuminate\Cache\Repository as Cache;

class S3Service implements ObjectStorageInterface
{
    public function __construct(
        private ?S3ClientInterface $s3Client,
        private Cache $cache,
        private MediaMetadataService $mediaMetadataService,
        private SongRepository $songRepository,
    ) {
    }

    public function getSongPublicUrl(Song $song): string
    {
        return $this->cache->remember("OSUrl/$song->id", now()->addHour(), function () use ($song): string {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $song->s3_params['bucket'],
                'Key' => $song->s3_params['key'],
            ]);

            // Here we specify that the request is valid for 1 hour.
            // We'll also cache the public URL for future reuse.
            $request = $this->s3Client->createPresignedRequest($cmd, '+1 hour');
            return (string) $request->getUri();
        });
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
        $path = Song::getPathFromS3BucketAndKey($bucket, $key);

        $artist = Artist::getOrCreate($artistName);

        $albumArtist = $albumArtistName && $albumArtistName !== $artistName
            ? Artist::getOrCreate($albumArtistName)
            : $artist;

        $album = Album::getOrCreate($albumArtist, $albumName);

        if ($cover) {
            $this->mediaMetadataService->writeAlbumCover(
                $album,
                base64_decode($cover['data'], true),
                $cover['extension']
            );
        }

        $song = Song::query()->updateOrCreate(['id' => Helper::getFileHash($path)], [
            'path' => $path,
            'album_id' => $album->id,
            'artist_id' => $artist->id,
            'title' => $title,
            'length' => $duration,
            'track' => $track,
            'lyrics' => $lyrics,
            'mtime' => time(),
        ]);

        event(new LibraryChanged());

        return $song;
    }

    public function deleteSongEntry(string $bucket, string $key): void
    {
        $path = Song::getPathFromS3BucketAndKey($bucket, $key);
        $song = $this->songRepository->getOneByPath($path);

        throw_unless((bool) $song, SongPathNotFoundException::create($path));

        $song->delete();
        event(new LibraryChanged());
    }
}
