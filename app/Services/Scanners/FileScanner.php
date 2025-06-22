<?php

namespace App\Services\Scanners;

use App\Services\SimpleLrcReader;
use App\Values\Scanning\ScanInformation;
use getID3;
use Illuminate\Support\Arr;
use RuntimeException;
use SplFileInfo;

class FileScanner
{
    public function __construct(private readonly getID3 $getID3, private readonly SimpleLrcReader $lrcReader)
    {
    }

    public function scan(string|SplFileInfo $path): ScanInformation
    {
        $file = $path instanceof SplFileInfo ? $path : new SplFileInfo($path);
        $filePath = $file->getRealPath();

        $raw = $this->getID3->analyze($filePath);

        if (Arr::get($raw, 'playtime_seconds')) {
            $syncError = Arr::get($raw, 'error.0') ?: (null);
        } else {
            $syncError = Arr::get($raw, 'error.0') ?: 'Empty file';
        }

        throw_if($syncError, new RuntimeException($syncError));

        $this->getID3->CopyTagsToComments($raw);

        return tap(
            ScanInformation::fromGetId3Info($raw, $filePath),
            function (ScanInformation $info) use ($filePath): void {
                $info->lyrics = $info->lyrics ?: $this->lrcReader->tryReadForMediaFile($filePath);
            }
        );
    }
}
