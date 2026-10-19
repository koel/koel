<?php

namespace App\Services\Scanners;

use App\Models\Song;
use App\Repositories\DuplicateUploadRepository;
use App\Repositories\SongRepository;
use App\Services\AudioAnalysis\AnalyzeAudioOnScan;
use App\Services\SongService;
use App\Services\Transcoding\TranscodeOnScan;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class IndividualFileHandler
{
    public function __construct(
        private readonly SongService $songService,
        private readonly SongRepository $songRepository,
        private readonly FileScanner $fileScanner,
        private readonly TranscodeOnScan $transcodeOnScan,
        private readonly AnalyzeAudioOnScan $analyzeAudioOnScan,
        private readonly DuplicateUploadRepository $duplicateUploadRepository,
    ) {}

    public function handle(string $path, ScanConfiguration $config): ScanResult
    {
        try {
            if ($this->awaitsDuplicateDecision($path)) {
                return ScanResult::skipped($path);
            }

            if (!$config->force && $this->songRepository->findMtimeByPath($path) === File::lastModified($path)) {
                return ScanResult::skipped($path);
            }

            $info = $this->fileScanner->scan($path);
            $song = $this->songService->createOrUpdateSongFromScan($info, $config);

            if ($song) {
                $this->transcodeAndAnalyzeSong($song, $path);
            }

            return ScanResult::success($info->path);
        } catch (Throwable $e) {
            return ScanResult::error($path, $e->getMessage());
        }
    }

    private function awaitsDuplicateDecision(string $path): bool
    {
        return Str::contains($path, '__KOEL_UPLOADS_') && $this->duplicateUploadRepository->existsAtLocation($path);
    }

    private function transcodeAndAnalyzeSong(Song $song, string $path): void
    {
        $this->transcodeOnScan->transcodeSongIfNeeded($song, $path);
        $this->analyzeAudioOnScan->analyzeSongIfEnabled($song, $path);
    }
}
