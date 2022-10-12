<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\SongZipArchive;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class DownloadService
{
    public function __construct(private S3Service $s3Service)
    {
    }

    /**
     * Generic method to generate a download archive from various source types.
     *
     * @return string Full path to the generated archive
     */
    public function from(Playlist|Song|Album|Artist|Collection $downloadable): string
    {
        switch (get_class($downloadable)) {
            case Song::class:
                return $this->fromSong($downloadable);

            case Collection::class:
            case EloquentCollection::class:
                return $this->fromMultipleSongs($downloadable);

            case Album::class:
                return $this->fromAlbum($downloadable);

            case Artist::class:
                return $this->fromArtist($downloadable);

            case Playlist::class:
                return $this->fromPlaylist($downloadable);
        }

        throw new InvalidArgumentException('Unsupported download type.');
    }

    public function fromSong(Song $song): string
    {
        if ($song->s3_params) {
            // The song is hosted on Amazon S3.
            // We download it back to our local server first.
            $url = $this->s3Service->getSongPublicUrl($song);
            abort_unless((bool) $url, 404);

            $localPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($song->s3_params['key']);

            // The following function requires allow_url_fopen to be ON.
            // We're just assuming that to be the case here.
            copy($url, $localPath);
        } else {
            // The song is hosted locally. Make sure the file exists.
            $localPath = $song->path;
            abort_unless(file_exists($localPath), 404);
        }

        return $localPath;
    }

    private function fromMultipleSongs(Collection $songs): string
    {
        if ($songs->count() === 1) {
            return $this->fromSong($songs->first());
        }

        return (new SongZipArchive())
            ->addSongs($songs)
            ->finish()
            ->getPath();
    }

    private function fromPlaylist(Playlist $playlist): string
    {
        return $this->fromMultipleSongs($playlist->songs);
    }

    private function fromAlbum(Album $album): string
    {
        return $this->fromMultipleSongs($album->songs);
    }

    public function fromArtist(Artist $artist): string
    {
        // We cater to the case where the artist is an "album artist," which means she has songs through albums as well.
        $songs = $artist->albums->reduce(
            static fn (Collection $songs, Album $album) => $songs->merge($album->songs),
            $artist->songs
        )->unique('id');

        return $this->fromMultipleSongs($songs);
    }
}
