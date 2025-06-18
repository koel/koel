<?php

namespace App\Services\SongStorages\Concerns;

use App\Helpers\Ulid;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File as FileFacade;
use Symfony\Component\HttpFoundation\File\File;

trait MovesUploadedFile
{
    protected function moveUploadedFileToTemporaryLocation(UploadedFile $file): File
    {
        // Can't scan the uploaded file directly, as it apparently causes some misbehavior during idv3 tag reading.
        // Instead, we copy the file to a tmp directory and later scan it from there.
        $tmpDir = artifact_path('tmp/' . Ulid::generate());
        FileFacade::ensureDirectoryExists($tmpDir);

        return $file->move($tmpDir, $file->getClientOriginalName());
    }
}
