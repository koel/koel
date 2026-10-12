<?php

namespace App\Http\Controllers\API;

use App\Enums\EmailChangeResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ConfirmEmailChangeRequest;
use App\Models\User;
use App\Services\EmailChangeService;
use Illuminate\Http\Response;

class ConfirmEmailChangeController extends Controller
{
    public function __invoke(User $user, ConfirmEmailChangeRequest $request, EmailChangeService $emailChangeService)
    {
        $result = $emailChangeService->confirmChange(
            $user,
            $request->validated('email'),
            $request->validated('current'),
            $request->validated('token'),
        );

        return match ($result) {
            EmailChangeResult::CHANGED => response()->noContent(),
            EmailChangeResult::TAKEN => abort(Response::HTTP_CONFLICT),
            EmailChangeResult::OUTDATED, EmailChangeResult::SINGLE_SIGN_ON => abort(Response::HTTP_FORBIDDEN),
        };
    }
}
