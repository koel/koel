<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Repositories\SongRepository;
use App\Services\DownloadService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DownloadController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly DownloadService $downloadService,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $song = $this->songRepository->getOne($request->id);
        $this->authorize('access', $song);

        return (
            $this->downloadService
                ->getDownloadable(new Collection([$song]))
                ?->toResponse() ?? throw new ModelNotFoundException()
        );
    }
}
