<?php

namespace App\Http\Controllers\API\MediaBrowser;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\MediaBrowser\PaginateFolderSongsRequest;
use App\Http\Resources\SongFileResource;
use App\Repositories\FolderRepository;
use App\Repositories\SongRepository;

#[RequiresPlus]
class PaginateFolderSongsController extends Controller
{
    public function __invoke(
        PaginateFolderSongsRequest $request,
        FolderRepository $folderRepository,
        SongRepository $songRepository,
    ) {
        $folder = $folderRepository->findOneByPublicId($request->folder);

        if ($folder) {
            $this->authorize('browse', $folder);
        }

        return SongFileResource::collection($songRepository->paginateInFolder(
            cursor: $request->cursor,
            folder: $folder,
        ));
    }
}
