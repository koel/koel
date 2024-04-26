<?php

namespace App\Services\SongStorages\Concerns;

use App\Exceptions\SongUploadFailedException;
use App\Models\User;
use App\Services\FileScanner;
use App\Values\ScanConfiguration;
use App\Values\ScanResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

trait ScansUploadedFile
{
    protected function scanUploadedFile(FileScanner $scanner, UploadedFile $file, User $uploader): ScanResult
    {
        // Can't scan the uploaded file directly, as it apparently causes some misbehavior during idv3 tag reading.
        // Instead, we copy the file to the tmp directory and scan it from there.
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'koel_tmp';
        File::ensureDirectoryExists($tmpDir);

        $tmpFile = $file->move($tmpDir, $file->getClientOriginalName());

        $result = $scanner->setFile($tmpFile)
            ->scan(ScanConfiguration::make(
                owner: $uploader,
                makePublic: $uploader->preferences->makeUploadsPublic
            ));

        throw_if($result->isError(), new SongUploadFailedException($result->error));

        return $result;
    }
}
