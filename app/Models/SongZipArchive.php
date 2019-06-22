<?php

namespace App\Models;

use App\Facades\Download;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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
     * @throws RuntimeException
     */
    public function __construct($path = '')
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Downloading multiple files requires ZipArchive module.');
        }

        if ($path) {
            $this->path = $path;
        } else {
            // We use system's temp dir instead of storage_path() here, so that the generated files
            // can be cleaned up automatically after server reboot.
            $this->path = sprintf('%s%skoel-download-%s.zip', sys_get_temp_dir(), DIRECTORY_SEPARATOR, uniqid());
        }

        $this->archive = new ZipArchive();

        if ($this->archive->open($this->path, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Cannot create zip file.');
        }
    }

    /**
     * Add multiple songs into the archive.
     */
    public function addSongs(Collection $songs): self
    {
        $songs->each([$this, 'addSong']);

        return $this;
    }

    /**
     * Add a single song into the archive.
     */
    public function addSong(Song $song): self
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
     */
    public function finish(): self
    {
        $this->archive->close();

        return $this;
    }

    /**
     * Get the path to the archive.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getArchive(): ZipArchive
    {
        return $this->archive;
    }
}
