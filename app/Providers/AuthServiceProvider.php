<?php

namespace App\Providers;

use App\Hooks\Filter;
use App\Models\User;
use App\Services\Auth\TokenManager;
use App\Services\SettingService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use SensitiveParameter;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        Auth::viaRequest('token-via-query-parameter', static function (Request $request): ?User {
            $token = $request->get('api_token') ?: $request->get('t');

            return app(TokenManager::class)->getUserFromPlainTextToken($token ?: '');
        });

        $this->setPasswordDefaultRules();

        ResetPassword::toMailUsing(static function (User $user, #[SensitiveParameter] string $token): MailMessage {
            $branding = app(SettingService::class)->getBranding($user->organization);
            $payload = base64_encode($user->getEmailForPasswordReset() . "|$token");

            return (new MailMessage())
                ->subject("Reset your {$branding->name} password")
                ->markdown('emails.users.reset-password', [
                    'branding' => $branding,
                    'user' => $user,
                    'url' => apply_filters(Filter::PASSWORD_RESET_URL, client_url("reset-password/$payload"), $user),
                    'expiresInMinutes' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire'),
                ]);
        });
    }

    private function setPasswordDefaultRules(): void
    {
        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->symbols()->uncompromised()
            : Password::min(6));
    }
}
