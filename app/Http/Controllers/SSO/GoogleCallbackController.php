<?php

namespace App\Http\Controllers\SSO;

use App\Facades\License;
use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use App\Services\UserService;
use App\Values\SSOUser;
use Laravel\Socialite\Facades\Socialite;

class GoogleCallbackController extends Controller
{
    public function __invoke(AuthenticationService $auth, UserService $userService)
    {
        assert(License::isPlus());

        $user = Socialite::driver('google')->user();
        $user = $userService->createOrUpdateUserFromSSO(SSOUser::fromSocialite($user, 'Google'));

        return view('sso-callback')->with('token', $auth->logUserIn($user)->toArray());
    }
}
