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
            $info = $this->fileScanner->scan($path);
            $song = $this->songService->createOrUpdateSongFromScan($info, $config);

            if ($song->wasRecentlyCreated) {
                return ScanResult::success($info->path);
            }

            return !$song->isFileModified($info) && !$config->force
                ? ScanResult::skipped($info->path)
                : ScanResult::success($info->path);
        } catch (Throwable $e) {
            return ScanResult::error($path, $e->getMessage());
        }
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
