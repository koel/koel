<?php

namespace App\Providers;

use App\Models\User;
use App\Services\TokenManager;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        Auth::viaRequest('token-via-query-parameter', static function (Request $request): ?User {
            /** @var TokenManager $tokenManager */
            $tokenManager = app(TokenManager::class);

            $token = $request->get('api_token') ?: $request->get('t');

            return $tokenManager->getUserFromPlainTextToken($token ?: '');
        });

        $this->setPasswordDefaultRules();

        ResetPassword::createUrlUsing(static function (User $user, string $token): string {
            $payload = base64_encode($user->getEmailForPasswordReset() . "|$token");

            return url("/#/reset-password/$payload");
        });
    }

    private function setPasswordDefaultRules(): void
    {
        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->symbols()->uncompromised()
            : Password::min(6));
    }
}
