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
    public function __construct(private readonly UserService $userService)
    {
    }

    public function tryGetProxyAuthenticatedUserFromRequest(Request $request): ?User
    {
        if (!self::validateProxyIp($request)) {
            return null;
        }

        try {
            return $this->userService->createOrUpdateUserFromSso(SsoUser::fromProxyAuthRequest($request));
        } catch (Throwable $e) {
            Log::error($e->getMessage(), ['exception' => $e]);
        }

        return null;
    }

    private static function validateProxyIp(Request $request): bool
    {
        return IpUtils::checkIp(
            $request->server->get('REMOTE_ADDR'),
            config('koel.proxy_auth.allow_list')
        );
    }
}
