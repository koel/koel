<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\SongZipArchive;
use Exception;
use Illuminate\Support\Collection;

class Download
{
    /**
     * Generic method to generate a download archive from various source types.
     *
     * @param Song|Collection<Song>|Album|Artist|Playlist $mixed
     *
     * @throws Exception
     *
     * @return string Full path to the generated archive
     */
    public function from($mixed)
    {
        if ($mixed instanceof Song) {
            return $this->fromSong($mixed);
        } elseif ($mixed instanceof Collection) {
            return $this->fromMultipleSongs($mixed);
        } elseif ($mixed instanceof Album) {
            return $this->fromAlbum($mixed);
        } elseif ($mixed instanceof Artist) {
            return $this->fromArtist($mixed);
        } elseif ($mixed instanceof Playlist) {
            return $this->fromPlaylist($mixed);
        } else {
            throw new Exception('Unsupported download type.');
        }
    }

    /**
     * Generate the downloadable path for a song.
     *
     * @param Song $song
     *
     * @return string
     */
    public function fromSong(Song $song)
    {
        if ($s3Params = $song->s3_params) {
            // The song is hosted on Amazon S3.
            // We download it back to our local server first.
            $localPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.basename($s3Params['key']);
            $url = $song->getObjectStoragePublicUrl();

            abort_unless($url, 404);

            // The following function require allow_url_fopen to be ON.
            // We're just assuming that to be the case here.
            copy($url, $localPath);
        } else {
            // The song is hosted locally. Make sure the file exists.
            abort_unless(file_exists($song->path), 404);
            $localPath = $song->path;
        }

        // The BinaryFileResponse factory only accept ASCII-only file names.
        if (ctype_print($localPath)) {
            return $localPath;
        }

        // For those with high-byte characters in names, we copy it into a safe name
        // as a workaround.
        $newPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .utf8_decode(basename($song->path));

        if ($s3Params) {
            // If the file is downloaded from S3, we rename it directly.
            // This will save us some disk space.
            rename($localPath, $newPath);
        } else {
            // Else we copy it to another file to not mess up the original one.
            copy($localPath, $newPath);
        }

        return $newPath;
    }

    /**
     * Generate a downloadable path of multiple songs in zip format.
     *
     * @param Collection $songs
     *
     * @throws Exception
     *
     * @return string
     */
    protected function fromMultipleSongs(Collection $songs)
    {
        if ($songs->count() === 1) {
            return $this->fromSong($songs->first());
        }

        return (new SongZipArchive())
            ->addSongs($songs)
            ->finish()
            ->getPath();
    }

    /**
     * @param Playlist $playlist
     *
     * @throws Exception
     *
     * @return string
     */
    protected function fromPlaylist(Playlist $playlist)
    {
        return $this->fromMultipleSongs($playlist->songs);
    }

    /**
     * @param Album $album
     *
     * @throws Exception
     *
     * @return string
     */
    protected function fromAlbum(Album $album)
    {
        return $this->fromMultipleSongs($album->songs);
    }

    /**
     * @param Artist $artist
     *
     * @throws Exception
     *
     * @return string
     */
    protected function fromArtist(Artist $artist)
    {
        return $this->fromMultipleSongs($artist->songs);
    }
}
