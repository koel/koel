<?php

namespace App\Http\Controllers\SSO;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use App\Services\UserService;
use App\Values\User\SsoUser;
use Laravel\Socialite\Facades\Socialite;

#[RequiresPlus]
class AutheliaCallbackController extends Controller
{
    public function __invoke(AuthenticationService $auth, UserService $userService)
    {
        $user = Socialite::driver('authelia')->user();
        $user = $userService->createOrUpdateUserFromSso(SsoUser::fromSocialite($user, 'Authelia'));

        return view('sso-callback')->with('token', $auth->logUserIn($user)->toArray());
    }
}
