<?php

namespace App\Http\Controllers\Subsonic;

use App\Exceptions\Subsonic\DataNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetUserRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Image\ImageStorage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetAvatarController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ImageStorage $imageStorage,
    ) {}

    /** @param User $user */
    public function __invoke(GetUserRequest $request, Authenticatable $user)
    {
        $target = $this->userRepository->findOneByEmailInOrganization(
            $request->username,
            $user->organization,
        ) ?? throw new ModelNotFoundException();

        throw_unless($target->has_custom_avatar, DataNotFoundException::class, 'Avatar not set.');

        $fileName = $target->getRawOriginal('avatar');

        throw_unless($this->imageStorage->exists($fileName), DataNotFoundException::class, 'Avatar file not found.');

        return ImageStorage::disk()->response($fileName);
    }
}
