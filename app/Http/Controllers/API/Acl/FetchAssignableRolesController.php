<?php

namespace App\Http\Controllers\API\Acl;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Acl;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchAssignableRolesController extends Controller
{
    /** @param User $user */
    public function __construct(private readonly Acl $acl, private readonly Authenticatable $user)
    {
    }

    public function __invoke()
    {
        $this->authorize('manage', User::class);

        return response()->json([
            'roles' => $this->acl->getAssignableRolesForUser($this->user)->toArray(),
        ]);
    }
}
