<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use Exception;
use Illuminate\Support\Collection;
use Log;
use ZipArchive;

class Download
{
    /**
     * Generic method to generate a download archive from various source types.
     *
     * @param Song|Collection<Song>|Album|Artist|Playlist $mixed
     *
     * @return string Full path to the generated archive
     */
    public function from($mixed)
    {
        if (is_a($mixed, Song::class)) {
            return $this->fromSong($mixed);
        } elseif (is_a($mixed, Collection::class)) {
            return $this->fromMultipleSongs($mixed);
        } elseif (is_a($mixed, Album::class)) {
            return $this->fromAlbum($mixed);
        } elseif (is_a($mixed, Artist::class)) {
            return $this->fromArtist($mixed);
        } elseif (is_a($mixed, Playlist::class)) {
            return $this->fromPlaylist($mixed);
        } else {
            throw new Exception('Unsupport download type.');
        }
    }

    protected function fromSong(Song $song)
    {
        // Maybe more interesting things can be added in the future (ID3 writing, perhaps).
        // For now, we simply return the song's path.
        return $song->path;
    }

    protected function fromMultipleSongs(Collection $songs)
    {
        if ($songs->count() === 1) {
            return $this->fromSong($songs->first());
        }

        if (!class_exists('ZipArchive')) {
            throw new Exception('Downloading multiple files requires ZipArchive module.');
        }

        // Start gathering the songs into a zip file.
        $zip = new ZipArchive();

        // We use system's temp dir instead storage_path() here, so that the generated files
        // can be cleaned up automatically after server reboot.
        $filename = rtrim(sys_get_temp_dir(), '/').'/koel-download-'.uniqid().'.zip';
        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            throw new Exception('Cannot create zip file.');
        }

        $localNames = [
            // The data will follow this format:
            // 'duplicated-name.mp3' => currentFileIndex
        ];

        $songs->each(function ($s) use ($zip, &$localNames) {
            try {
                // We add all files into the zip archive as a flat structure.
                // As a result, there can be duplicate file names.
                // The following several lines are to make sure each file name is unique.
                $name = basename($s->path);
                if (array_key_exists($name, $localNames)) {
                    ++$localNames[$name];
                    $parts = explode('.', $name);
                    $ext = $parts[count($parts) - 1];
                    $parts[count($parts) - 1] = $localNames[$name].".$ext";
                    $name = implode('.', $parts);
                } else {
                    $localNames[$name] = 1;
                }

                $zip->addFile($s->path, $name);
            } catch (Exception $e) {
                Log::error($e);
            }
        });

        $zip->close();

        return $filename;
    }

    protected function fromPlaylist(Playlist $playlist)
    {
        return $this->fromMultipleSongs($playlist->songs);
    }

    protected function fromAlbum(Album $album)
    {
        return $this->fromMultipleSongs($album->songs);
    }

    protected function fromArtist(Artist $artist)
    {
        // Don't forget the contributed songs.
        $songs = $artist->songs->merge($artist->getContributedSongs());

        return $this->fromMultipleSongs($songs);
    }
}
