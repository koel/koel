<?php

namespace App\Models;

use App\Facades\Download;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use ZipArchive;

class SongZipArchive
{
    private ZipArchive $archive;

    private string $path;

    /**
     * Names of the files in the archive
     * Format: [file-name.mp3' => currentFileIndex].
     */
    private array $fileNames = [];

    public function __construct(string $path = '')
    {
        $this->path = $path ?: self::generateRandomArchivePath();

        $this->archive = new ZipArchive();

        if ($this->archive->open($this->path, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Cannot create zip file.');
        }
    }

    public function addSongs(Collection $songs): self
    {
        $songs->each(function (Song $song): void {
            $this->addSong($song);
        });

        return $this;
    }

    public function addSong(Song $song): self
    {
        try {
            $path = Download::fromSong($song);
            $this->archive->addFile($path, $this->generateZipContentFileNameFromPath($path));
        } catch (Throwable $e) {
            Log::error($e);
        }

        return $this;
    }

    public function finish(): self
    {
        $this->archive->close();

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * We add all files into the zip archive as a flat structure.
     * As a result, there can be duplicate file names.
     * This method makes sure each file name is unique in the zip archive.
     */
    private function generateZipContentFileNameFromPath(string $path): string
    {
        $name = basename($path);

        if (array_key_exists($name, $this->fileNames)) {
            ++$this->fileNames[$name];
            $parts = explode('.', $name);
            $ext = $parts[count($parts) - 1];
            $parts[count($parts) - 1] = $this->fileNames[$name] . ".$ext";
            $name = implode('.', $parts);
        } else {
            $this->fileNames[$name] = 1;
        }

        return $name;
    }

    private static function generateRandomArchivePath(): string
    {
        // We use system's temp dir instead of storage_path() here, so that the generated files
        // can be cleaned up automatically after server reboot.
        return sprintf('%s%skoel-download-%s.zip', sys_get_temp_dir(), DIRECTORY_SEPARATOR, uniqid());
    }

    public function getArchive(): ZipArchive
    {
        return $this->archive;
    }
}
