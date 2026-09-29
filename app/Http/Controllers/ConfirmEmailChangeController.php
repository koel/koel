<?php

namespace App\Http\Controllers;

use App\Enums\EmailChangeResult;
use App\Http\Requests\ConfirmEmailChangeRequest;
use App\Models\User;
use App\Services\EmailChangeService;

class ConfirmEmailChangeController extends Controller
{
    public function show(User $user, ConfirmEmailChangeRequest $request)
    {
        return view('email-change-confirm', ['newEmail' => $request->email, 'action' => $request->fullUrl()]);
    }

    public function confirm(User $user, ConfirmEmailChangeRequest $request, EmailChangeService $emailChangeService)
    {
        $result = $emailChangeService->confirmChange($user, $request->email, $request->current, $request->token);

        [$title, $details] = match ($result) {
            EmailChangeResult::CHANGED => ['Email changed', "Your email address is now {$request->email}."],
            EmailChangeResult::OUTDATED => [
                'Invalid link',
                'The link is invalid or has expired.',
            ],
            EmailChangeResult::TAKEN => ['Address in use', 'Another account already uses this email address.'],
            EmailChangeResult::SINGLE_SIGN_ON => [
                'Not possible',
                'This account signs in with single sign-on, so its email address cannot change here.',
            ],
        };

        return view('email-change', ['title' => $title, 'details' => $details]);
    }
}
