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
        $folder = $folderRepository->findOneByPublicId(request('folder'));

        if ($folder) {
            $this->authorize('browse', $folder);
        }

        $view = $browser->getSubfolderView($folder);

        return [
            'current' => $view['current'] ? new FolderResource($view['current']) : null,
            'ancestors' => FolderResource::collection($view['ancestors']),
            'subfolders' => FolderResource::collection($view['subfolders']),
        ];
    }
}
