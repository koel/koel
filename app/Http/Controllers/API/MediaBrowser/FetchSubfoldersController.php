<?php

namespace App\Http\Controllers\API\MediaBrowser;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FolderResource;
use App\Repositories\FolderRepository;
use App\Services\MediaBrowser;
use Illuminate\Database\Eloquent\Collection;

#[RequiresPlus]
class FetchSubfoldersController extends Controller
{
    public function __invoke(MediaBrowser $browser, FolderRepository $folderRepository)
    {
        $folder = $folderRepository->findOneByPublicId(request('folder'));

        if ($folder) {
            $this->authorize('browse', $folder);
        }

        $ancestors = $folder ? $folderRepository->getAncestors($folder) : new Collection();
        $subfolders = $folderRepository->getSubfolders($folder);

        $serializable = $ancestors->merge($subfolders);

        if ($folder) {
            $serializable->push($folder);
        }

        $serializable->loadMissing('uploader');

        return [
            'current' => $folder ? new FolderResource($folder) : null,
            'ancestors' => FolderResource::collection($ancestors),
            'subfolders' => FolderResource::collection($subfolders),
        ];
    }
}
