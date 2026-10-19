<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Repositories\SongRepository;
use App\Services\DownloadService;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DownloadController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly DownloadService $downloadService,
        #[Config('koel.download.allow')]
        private readonly bool $downloadAllowed,
    ) {}

    public function __invoke(IdRequest $request)
    {
        throw_unless($this->downloadAllowed, AccessDeniedHttpException::class);

        $song = $this->songRepository->getOne($request->id);
        $this->authorize('access', $song);

        return $this->downloadService->getDownloadable($song)?->toResponse() ?? throw new ModelNotFoundException();
    }
}
