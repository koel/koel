<?php

namespace App\Providers;

use App\Models\Playlist;
use App\Models\User;
use App\Policies\PlaylistPolicy;
use App\Policies\UserPolicy;
use App\Services\TokenManager;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Playlist::class => PlaylistPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Auth::viaRequest('token-via-query-parameter', static function (Request $request): ?User {
            /** @var TokenManager $tokenManager */
            $tokenManager = app(TokenManager::class);

            return $tokenManager->getUserFromPlainTextToken($request->api_token ?: '');
        });

        $this->setPasswordDefaultRules();
    }

    private function setPasswordDefaultRules(): void
    {
        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->symbols()->uncompromised()
            : Password::min(6));
    }
}
