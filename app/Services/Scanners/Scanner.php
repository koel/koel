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

            return $song->mtime === $info->mTime && !$config->force
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
        $audioExtensions = [
            'mp3',  // audio/mpeg
            'mp4', 'm4a', // audio/mp4
            'aac',  // audio/aac
            'ogg',  // audio/ogg, audio/vorbis, audio/opus, audio/speex, audio/flac
            'opus', // audio/opus
            'flac', 'fla', // audio/flac, audio/x-flac
            'amr',  // audio/amr
            'ac3',  // audio/ac3
            'dts',  // audio/dts
            'ra', 'rm', // audio/vnd.rn-realaudio
            'wma',  // audio/x-ms-wma
            'au',   // audio/basic
            'wav',  // audio/vnd.wave, audio/x-wav
            'aiff', 'aif', 'aifc', // audio/aiff, audio/x-aiff
            'mka',  // audio/x-matroska
            'ape',  // audio/x-ape, audio/x-monkeys-audio
            'tta',  // audio/tta
            'wv', 'wvc', // audio/x-wavpack
            'ofr', 'ofs', // audio/x-optimfrog
            'shn',  // audio/x-shorten, audio/xmms-shn
            'lpac', // audio/x-lpac
            'dsf', 'dff', // audio/x-dsd
            'spx',  // audio/x-speex
            'dss',  // audio/x-dss
            'aa',   // audio/x-audible
            'vqf',  // audio/x-twinvq, audio/vqf
            'mpc', 'mp+', // audio/x-musepack
            'voc',  // audio/x-voc
        ];

        $nameRegex = '/\.(' . implode('|', $audioExtensions) . ')$/i';

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
