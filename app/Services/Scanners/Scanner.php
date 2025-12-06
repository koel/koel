<?php

namespace App\Services\Scanners;

use App\Repositories\SongRepository;
use App\Services\SongService;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use Illuminate\Support\Facades\File;
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
            $song = $this->songRepository->findOneByPath($path);

            // Use last modified time instead of hash to determine if the file is modified
            // as calculating hash for every file is too time-consuming.
            // See https://github.com/koel/koel/issues/2165.
            if (!$config->force && $song && !$song->isFileModified(File::lastModified($path))) {
                return ScanResult::skipped($path);
            }

            $info = $this->fileScanner->scan($path);
            $this->songService->createOrUpdateSongFromScan($info, $config, $song);

            return ScanResult::success($info->path);
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
