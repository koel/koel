<?php

namespace App\Services\Scanners;

use App\Repositories\SongRepository;
use App\Services\SongService;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use Symfony\Component\Finder\Finder;
use Throwable;

abstract class Scanner
{
    public function __construct(
        protected SongService $songService,
        protected SongRepository $songRepository,
        protected FileScanner $fileScanner,
        protected Finder $finder
    ) {
    }

    protected function handleIndividualFile(string $path, ScanConfiguration $config): ScanResult
    {
        try {
            $lrcModified = false;
            $info = $this->fileScanner->scan($path);

            // Check if song already exists
            $existingSong = $this->songRepository->findOneByPath($path);

            if ($existingSong) {
                $lrcModified = $this->isLrcFileModified($existingSong, $path);

                // If only LRC changed, force update the lyrics
                if ($lrcModified && !$existingSong->isFileModified($info)) {
                    $existingSong->update(['lyrics' => $info->lyrics]);
                    return ScanResult::success($info->path);
                }
            }

            $song = $this->songService->createOrUpdateSongFromScan($info, $config);

            if ($song->wasRecentlyCreated) {
                return ScanResult::success($info->path);
            }

            $fileModified = $song->isFileModified($info);

            return !$fileModified && !$lrcModified && !$config->force
                ? ScanResult::skipped($info->path)
                : ScanResult::success($info->path);
        } catch (Throwable $e) {
            return ScanResult::error($path, $e->getMessage());
        }
    }

    /**
     * Check if the LRC file has been modified since the last scan.
     */
    protected function isLrcFileModified(object $song, string $mediaFilePath): bool
    {
        // Get potential LRC file path
        $lrcPath = preg_replace('/\.[^.]+$/', '.lrc', $mediaFilePath);

        if (!file_exists($lrcPath)) {
            // Check uppercase extension
            $lrcPath = preg_replace('/\.[^.]+$/', '.LRC', $mediaFilePath);
            if (!file_exists($lrcPath)) {
                // No LRC file exists
                // If song has lyrics, they might have been removed
                return !empty($song->lyrics);
            }
        }

        // LRC file exists
        $lrcMtime = filemtime($lrcPath);
        $songMtime = $song->mtime;

        // If LRC is newer than when the song was last scanned, consider it modified
        return $lrcMtime > $songMtime;
    }

    /**
     * Gather all applicable files in a given directory.
     *
     * @param string $path The directory's full path
     */
    protected function gatherFiles(string $path): Finder
    {
        $nameRegex = '/\.(' . implode('|', collect_accepted_audio_extensions()) . ')$/i';

        return $this->finder::create()
            ->ignoreUnreadableDirs()
            ->ignoreDotFiles((bool) config('koel.ignore_dot_files')) // https://github.com/koel/koel/issues/450
            ->files()
            ->followLinks()
            ->name($nameRegex)
            ->in($path);
    }

    protected static function setSystemRequirements(): void
    {
        if (!app()->runningInConsole()) {
            set_time_limit(config('koel.scan.timeout'));
        }

        if (config('koel.scan.memory_limit')) {
            ini_set('memory_limit', config('koel.scan.memory_limit') . 'M');
        }
    }
}
