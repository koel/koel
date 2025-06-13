<?php

namespace App\Http\Controllers\API\PlaylistCollaboration;

use App\Attributes\RequiresPlus;
use App\Exceptions\CannotRemoveOwnerFromPlaylistException;
use App\Exceptions\NotAPlaylistCollaboratorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistCollaboration\PlaylistCollaboratorDestroyRequest;
use App\Models\Playlist;
use App\Repositories\UserRepository;
use App\Services\PlaylistCollaborationService;
use Illuminate\Http\Response;

#[RequiresPlus]
class PlaylistCollaboratorController extends Controller
{
    public function __construct(
        private readonly PlaylistCollaborationService $service,
        private readonly UserRepository $userRepository
    ) {
    }

    public function index(Playlist $playlist)
    {
        $this->authorize('collaborate', $playlist);

        return $this->service->getCollaborators(playlist: $playlist, includingOwner: true);
    }

    public function destroy(Playlist $playlist, PlaylistCollaboratorDestroyRequest $request)
    {
        $this->authorize('own', $playlist);

        $collaborator = $this->userRepository->getOneByPublicId($request->collaborator);

        try {
            $this->service->removeCollaborator($playlist, $collaborator);

            return response()->noContent();
        } catch (CannotRemoveOwnerFromPlaylistException) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot remove yourself from your own playlist.');
        } catch (NotAPlaylistCollaboratorException) {
            abort(Response::HTTP_NOT_FOUND, 'This user is not a collaborator of this playlist.');
        }
    }
}
