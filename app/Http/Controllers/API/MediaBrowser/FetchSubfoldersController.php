<?php

namespace App\Http\Controllers\API\MediaBrowser;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FolderResource;
use App\Repositories\FolderRepository;
use App\Services\MediaBrowser;

#[RequiresPlus]
class FetchSubfoldersController extends Controller
{
    public function __invoke(MediaBrowser $browser, FolderRepository $folderRepository)
    {
        $folder = $folderRepository->findByPath(request('path'));

        if ($folder) {
            $this->authorize('browse', $folder);
        }

        return FolderResource::collection($folderRepository->getSubfolders($folder));
    }
}
