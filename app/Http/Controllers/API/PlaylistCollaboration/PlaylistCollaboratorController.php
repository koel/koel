<?php

namespace App\Http\Controllers\API\PlaylistCollaboration;

use App\Exceptions\CannotRemoveOwnerFromPlaylistException;
use App\Exceptions\KoelPlusRequiredException;
use App\Exceptions\NotAPlaylistCollaboratorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistCollaboration\PlaylistCollaboratorDestroyRequest;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\PlaylistCollaborationService;
use Illuminate\Http\Response;

class PlaylistCollaboratorController extends Controller
{
    public function __construct(private PlaylistCollaborationService $service, private UserRepository $userRepository)
    {
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

        try {
            $this->service->removeCollaborator($playlist, $collaborator);

            return response()->noContent();
        } catch (KoelPlusRequiredException) {
            abort(Response::HTTP_FORBIDDEN, 'This feature is only available for Plus users.');
        } catch (CannotRemoveOwnerFromPlaylistException) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot remove yourself from your own playlist.');
        } catch (NotAPlaylistCollaboratorException) {
            abort(Response::HTTP_NOT_FOUND, 'This user is not a collaborator of this playlist.');
        }
    }
}
