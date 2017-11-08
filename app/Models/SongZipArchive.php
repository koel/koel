<?php

namespace App\Models;

use App\Facades\Download;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class SongZipArchive
{
    /**
     * @var ZipArchive
     */
    protected $archive;

    /**
     * Path to the zip archive.
     *
     * @var string
     */
    protected $path;

    /**
     * Names of the files in the archive
     * Format: [file-name.mp3' => currentFileIndex].
     *
     * @var array
     */
    protected $fileNames = [];

    /**
     * @param string $path
     *
     * @throws Exception
     */
    public function __construct($path = '')
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('Downloading multiple files requires ZipArchive module.');
        }

        // We use system's temp dir instead of storage_path() here, so that the generated files
        // can be cleaned up automatically after server reboot.
        $this->path = $path ?: $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'koel-download-'.uniqid().'.zip';

        $this->archive = new ZipArchive();

        if ($this->archive->open($this->path, ZipArchive::CREATE) !== true) {
            throw new Exception('Cannot create zip file.');
        }
    }

    /**
     * Add multiple songs into the archive.
     *
     * @param Collection $songs
     *
     * @return $this
     */
    public function addSongs(Collection $songs)
    {
        $songs->each(function ($song) {
            $this->addSong($song);
        });

        return $this;
    }

    /**
     * Add a single song into the archive.
     *
     * @param Song $song
     *
     * @return $this
     */
    public function addSong(Song $song)
    {
        try {
            $path = Download::fromSong($song);

            // We add all files into the zip archive as a flat structure.
            // As a result, there can be duplicate file names.
            // The following several lines are to make sure each file name is unique.
            $name = basename($path);
            if (array_key_exists($name, $this->fileNames)) {
                $this->fileNames[$name]++;
                $parts = explode('.', $name);
                $ext = $parts[count($parts) - 1];
                $parts[count($parts) - 1] = $this->fileNames[$name].".$ext";
                $name = implode('.', $parts);
            } else {
                $this->fileNames[$name] = 1;
            }

            $this->archive->addFile($path, $name);
        } catch (Exception $e) {
            Log::error($e);
        }

        return $this;
    }

    /**
     * Finish (close) the archive.
     *
     * @return $this
     */
    public function finish()
    {
        $this->archive->close();

        return $this;
    }

    /**
     * Get the path to the archive.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return ZipArchive
     */
    public function getArchive()
    {
        return $this->archive;
    }
}
