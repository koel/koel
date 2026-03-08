<?php

namespace App\Services\Scanners;

use Symfony\Component\Finder\Finder;

abstract class Scanner
{
    public function __construct(
        protected readonly IndividualFileHandler $fileHandler,
        protected readonly Finder $finder,
    ) {}

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
            // @mago-ignore lint:no-ini-set
            ini_set('memory_limit', config('koel.scan.memory_limit') . 'M');
        }
    }
}
