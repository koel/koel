<?php

namespace App\Http\Controllers\API\PlaylistCollaboration;

use App\Facades\License;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistCollaboration\PlaylistCollaboratorDestroyRequest;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\PlaylistCollaborationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class PlaylistCollaboratorController extends Controller
{
    /** @param User $user */
    public function __construct(
        private PlaylistCollaborationService $service,
        private UserRepository $userRepository,
        private ?Authenticatable $user
    ) {
    }

    public function index(Playlist $playlist)
    {
        $this->authorize('collaborate', $playlist);

        return $this->service->getCollaborators($playlist);
    }

    public function destroy(Playlist $playlist, PlaylistCollaboratorDestroyRequest $request)
    {
        $this->authorize('own', $playlist);

        /** @var User $collaborator */
        $collaborator = $this->userRepository->getOne($request->collaborator);

        abort_unless(License::isPlus(), Response::HTTP_FORBIDDEN, 'This feature is only available for Plus users.');

        abort_if(
            $collaborator->is($this->user),
            Response::HTTP_FORBIDDEN,
            'You cannot remove yourself from your own playlist.'
        );

        abort_unless(
            $playlist->hasCollaborator($collaborator),
            Response::HTTP_NOT_FOUND,
            'This user is not a collaborator of this playlist.'
        );

        $this->service->removeCollaborator($playlist, $collaborator);
    }
}
