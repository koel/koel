<?php

namespace App\Services\Scanners;

use App\Repositories\SongRepository;
use App\Services\SongService;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use Illuminate\Support\Facades\File;
use Throwable;

class IndividualFileHandler
{
    public function __construct(
        private readonly SongService $songService,
        private readonly SongRepository $songRepository,
        private readonly FileScanner $fileScanner,
    ) {}

    public function handle(string $path, ScanConfiguration $config): ScanResult
    {
        try {
            $song = $this->songRepository->findOneByPath($path);

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
}
