<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetUserRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\File;

class GetAvatarController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function __invoke(GetUserRequest $request)
    {
        $target = $this->userRepository->findOneByName($request->username) ?? throw new ModelNotFoundException();

        if (!$target->has_custom_avatar) {
            return SubsonicResponse::error(70, 'Avatar not set.');
        }

        $path = image_storage_path($target->getRawOriginal('avatar'), ensureDirectoryExists: false);

        if (!$path || !File::isFile($path)) {
            return SubsonicResponse::error(70, 'Avatar file not found.');
        }

        return response()->file($path);
    }
}
