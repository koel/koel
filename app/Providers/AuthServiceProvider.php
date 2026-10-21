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
use Laravel\Sanctum\Sanctum;
use SensitiveParameter;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Routes opened as plain URLs (audio sources, links), which can't send an Authorization header.
     */
    private const array ROUTES_ACCEPTING_TOKEN_IN_QUERY = [
        'song.play',
        'radio.stream',
        'songs.download',
        'iTunes.viewSong',
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Auth::viaRequest('token-via-query-parameter', static function (Request $request): ?User {
            $token = self::getPlainTextTokenFromQuery($request);

            return app(TokenManager::class)->getUserFromPlainTextToken($token ?: '');
        });

        Sanctum::getAccessTokenFromRequestUsing(
            static fn (Request $request): ?string => (
                $request->bearerToken() ?: self::getPlainTextTokenFromQuery($request)
            ),
        );

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

    private static function getPlainTextTokenFromQuery(Request $request): ?string
    {
        if (!$request->routeIs(...self::ROUTES_ACCEPTING_TOKEN_IN_QUERY)) {
            return null;
        }

        return $request->query('api_token') ?: $request->query('t');
    }

    private function setPasswordDefaultRules(): void
    {
        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->symbols()->uncompromised()
            : Password::min(6));
    }
}
