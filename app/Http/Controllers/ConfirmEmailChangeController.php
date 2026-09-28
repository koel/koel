<?php

namespace App\Http\Controllers;

use App\Enums\EmailChangeResult;
use App\Http\Requests\ConfirmEmailChangeRequest;
use App\Models\User;
use App\Services\EmailChangeService;

class ConfirmEmailChangeController extends Controller
{
    public function __invoke(User $user, ConfirmEmailChangeRequest $request, EmailChangeService $emailChangeService)
    {
        [$title, $details] = match ($emailChangeService->confirmChange($user, $request->email, $request->current)) {
            EmailChangeResult::CHANGED => ['Email changed', "Your email address is now {$request->email}."],
            EmailChangeResult::OUTDATED => [
                'Link out of date',
                'This email address has changed since the link was sent.',
            ],
            EmailChangeResult::TAKEN => ['Address in use', 'Another account already uses this email address.'],
        };

        return view('email-change', ['title' => $title, 'details' => $details]);
    }
}
