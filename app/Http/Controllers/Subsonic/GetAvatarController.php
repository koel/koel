<?php

namespace App\Http\Controllers\Subsonic;

use App\Exceptions\Subsonic\DataNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetUserRequest;
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
        $target = $this->userRepository->findOneByEmail($request->username) ?? throw new ModelNotFoundException();

        throw_unless($target->has_custom_avatar, DataNotFoundException::class, 'Avatar not set.');

        $path = image_storage_path($target->getRawOriginal('avatar'), ensureDirectoryExists: false);
        throw_if(!$path || !File::isFile($path), DataNotFoundException::class, 'Avatar file not found.');

        return response()->file($path);
    }
}
