<?php

namespace App\Http\Controllers\SSO;

use App\Facades\License;
use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use App\Services\UserService;
use Laravel\Socialite\Facades\Socialite;

class GoogleCallbackController extends Controller
{
    public function __invoke(AuthenticationService $auth, UserService $userService)
    {
        assert(License::isPlus());

        $user = Socialite::driver('google')->user();
        $user = $userService->createOrUpdateUserFromSocialiteUser($user, 'Google');

        return view('sso-callback')->with('token', $auth->logUserIn($user)->toArray());
    }
}
