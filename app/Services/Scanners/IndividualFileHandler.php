<?php

namespace App\Services\Scanners;

use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\AudioAnalysis\AnalyzeAudioOnScan;
use App\Services\SongService;
use App\Services\Transcoding\TranscodeOnScan;
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
        private readonly TranscodeOnScan $transcodeOnScan,
        private readonly AnalyzeAudioOnScan $analyzeAudioOnScan,
    ) {}

    public function handle(string $path, ScanConfiguration $config): ScanResult
    {
        try {
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

    private function transcodeAndAnalyzeSong(Song $song, string $path): void
    {
        $this->transcodeOnScan->transcodeSongIfNeeded($song, $path);
        $this->analyzeAudioOnScan->analyzeSongIfEnabled($song, $path);
    }
}
