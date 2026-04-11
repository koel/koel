<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\GoogleMobileConsentRequest;
use App\Http\Requests\API\GoogleMobileLoginRequest;
use App\Repositories\UserRepository;
use App\Services\AuthenticationService;
use App\Services\ConsentService;
use App\Services\GoogleIdTokenVerifier;
use App\Services\SettingService;
use App\Services\UserService;
use App\Values\User\SsoUser;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class GoogleMobileSsoController extends Controller
{
    use ThrottlesLogins;

    public function __construct(
        private readonly GoogleIdTokenVerifier $verifier,
        private readonly AuthenticationService $auth,
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly SettingService $settingService,
        private readonly ConsentService $consentService,
    ) {}

    public function login(GoogleMobileLoginRequest $request): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        try {
            $ssoUser = $this->verifier->verify($request->id_token);
        } catch (Throwable) {
            $this->incrementLoginAttempts($request);
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid Google ID token');
        }

        $existingUser = $this->userRepository->findOneBySso($ssoUser);

        if ($existingUser) {
            $user = $this->userService->createOrUpdateUserFromSso($ssoUser);

            return response()->json($this->auth->logUserIn($user)->toArray());
        }

        return response()->json([
            'requires_consent' => true,
            'sso_user' => $ssoUser->toArray(),
            'legal_urls' => $this->settingService->getConsentLegalUrls(),
        ]);
    }

    public function consent(GoogleMobileConsentRequest $request): JsonResponse
    {
        $ssoUser = SsoUser::fromArray($request->sso_user);
        $user = $this->userService->createOrUpdateUserFromSso($ssoUser);

        $this->consentService->recordConsent($user, $request);

        return response()->json($this->auth->logUserIn($user)->toArray());
    }

    protected function username(): string
    {
        return 'id_token';
    }
}
