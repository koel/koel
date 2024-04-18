<?php

namespace App\Services;

use App\Models\User;
use App\Values\SSOUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

class ProxyAuthService
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function tryGetProxyAuthenticatedUserFromRequest(Request $request): ?User
    {
        if (!self::validateProxyIp($request)) {
            return null;
        }

        try {
            return $this->userService->createOrUpdateUserFromSSO(SSOUser::fromProxyAuthRequest($request));
        } catch (Throwable $e) {
            Log::error($e->getMessage(), ['exception' => $e]);
        }

        return null;
    }

    private static function validateProxyIp(Request $request): bool
    {
        return IpUtils::checkIp($request->ip(), config('koel.proxy_auth.allow_list'));
    }
}
