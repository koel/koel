<?php

namespace App\Observers;

use App\Models\Folder;

class FolderObserver
{
    public function creating(Folder $folder): void
    {
        $folder->hash ??= simple_hash($folder->path);
    }
}
