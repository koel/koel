<?php

namespace App\Http\Controllers\SSO;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthenticationService;
use App\Services\UserService;
use App\Values\User\SsoUser;
use Laravel\Socialite\Facades\Socialite;

#[RequiresPlus]
class OpenIDConnectCallbackController extends Controller
{
    public function __invoke(AuthenticationService $auth, UserService $userService)
    {
        $user = Socialite::driver('oidc')->user();
        $user = $userService->createOrUpdateUserFromSso(SsoUser::fromSocialite($user, 'OpenID Connect'));

        return view('sso-callback', ['token' => $auth->logUserIn($user)->toArray()]);
    }
}
