<?php

namespace App\Http\Controllers\API;

use App\Enums\Acl\Role;
use App\Exceptions\InvitationNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\AcceptUserInvitationRequest;
use App\Http\Requests\API\GetUserInvitationRequest;
use App\Http\Requests\API\InviteUserRequest;
use App\Http\Requests\API\RevokeUserInvitationRequest;
use App\Http\Resources\UserProspectResource;
use App\Models\User;
use App\Services\AuthenticationService;
use App\Services\UserInvitationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UserInvitationController extends Controller
{
    public function __construct(
        private readonly UserInvitationService $invitationService,
        private readonly AuthenticationService $auth,
    ) {
    }

    /** @param User $invitor */
    public function invite(InviteUserRequest $request, Authenticatable $invitor)
    {
        $this->authorize('manage', $invitor);

        $invitees = $this->invitationService->invite(
            $request->emails,
            $request->enum('role', Role::class),
            $invitor
        );

        return UserProspectResource::collection($invitees);
    }

    public function get(GetUserInvitationRequest $request)
    {
        try {
            return UserProspectResource::make($this->invitationService->getUserProspectByToken($request->token));
        } catch (InvitationNotFoundException) {
            abort(Response::HTTP_NOT_FOUND, 'The invitation token is invalid.');
        }
    }

    public function accept(AcceptUserInvitationRequest $request)
    {
        try {
            $user = $this->invitationService->accept($request->token, $request->name, $request->password);

            return response()->json($this->auth->login($user->email, $request->password)->toArray());
        } catch (InvitationNotFoundException) {
            abort(Response::HTTP_NOT_FOUND, 'The invitation token is invalid.');
        }
    }

    public function revoke(RevokeUserInvitationRequest $request)
    {
        $this->authorize('manage', User::class);

        try {
            $this->invitationService->revokeByEmail($request->email);

            return response()->noContent();
        } catch (InvitationNotFoundException) {
            abort(Response::HTTP_NOT_FOUND, 'The invitation token is invalid.');
        }
    }
}
