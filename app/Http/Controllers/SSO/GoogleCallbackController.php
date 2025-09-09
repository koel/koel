<?php

namespace App\Http\Controllers\SSO;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use App\Services\UserService;
use App\Values\User\SsoUser;
use Laravel\Socialite\Facades\Socialite;

#[RequiresPlus]
class GoogleCallbackController extends Controller
{
    public function __invoke(AuthenticationService $auth, UserService $userService)
    {
        $user = Socialite::driver('google')->user();
        $user = $userService->createOrUpdateUserFromSso(SsoUser::fromSocialite($user, 'Google'));

        return view('sso-callback')->with('token', $auth->logUserIn($user)->toArray());
    }
}
