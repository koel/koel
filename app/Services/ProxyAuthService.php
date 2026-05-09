<?php

namespace App\Services;

use App\Attributes\RequiresPlus;
use App\Models\User;
use App\Values\User\SsoUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

#[RequiresPlus]
class ProxyAuthService
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function tryGetProxyAuthenticatedUserFromRequest(Request $request): ?User
    {
        $remoteAddr = $request->server->get('REMOTE_ADDR');

        if (!self::validateProxyIp($request)) {
            Log::warning('[ProxyAuth] Remote address not in allow list', [
                'remote_addr' => $remoteAddr,
                'allow_list' => config('koel.proxy_auth.allow_list'),
            ]);

            return null;
        }

        $userHeader = config('koel.proxy_auth.user_header');

        if (!$request->header($userHeader)) {
            Log::warning('[ProxyAuth] User header not present on request', [
                'expected_header' => $userHeader,
                'remote_addr' => $remoteAddr,
            ]);

            return null;
        }

        try {
            return $this->userService->createOrUpdateUserFromSso(SsoUser::fromProxyAuthRequest($request));
        } catch (Throwable $e) {
            Log::error('[ProxyAuth] Failed to create or update user from SSO headers', [
                'exception' => $e,
                'expected_header' => $userHeader,
                'remote_addr' => $remoteAddr,
            ]);
        }

        return null;
    }

    private static function validateProxyIp(Request $request): bool
    {
        return IpUtils::checkIp($request->server->get('REMOTE_ADDR'), config('koel.proxy_auth.allow_list'));
    }
}
