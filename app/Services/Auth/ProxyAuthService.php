<?php

namespace App\Services\Auth;

use App\Attributes\RequiresPlus;
use App\Exceptions\ProxyAuthException;
use App\Exceptions\ProxyAuthIpNotAllowedException;
use App\Exceptions\ProxyAuthUserHeaderMissingException;
use App\Models\User;
use App\Services\UserService;
use App\Values\User\SsoUser;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

#[RequiresPlus]
class ProxyAuthService
{
    /** @param array<int, string> $allowList */
    public function __construct(
        private readonly UserService $userService,
        #[Config('koel.proxy_auth.allow_list')]
        private readonly array $allowList,
        #[Config('koel.proxy_auth.user_header')]
        private readonly string $userHeader,
    ) {}

    public function tryGetProxyAuthenticatedUserFromRequest(Request $request): ?User
    {
        $remoteAddr = $request->server->get('REMOTE_ADDR');

        try {
            $this->assertProxyIpAllowed($remoteAddr);
            $this->assertUserHeaderPresent($request, $remoteAddr);

            return $this->userService->createOrUpdateUserFromSso(SsoUser::fromProxyAuthRequest($request));
        } catch (ProxyAuthException $e) {
            Log::warning(sprintf('[ProxyAuth] %s', $e->getMessage()), $e->getContext());
        } catch (Throwable $e) {
            Log::error('[ProxyAuth] Failed to create or update user from SSO headers', [
                'exception' => $e,
                'expected_header' => $this->userHeader,
                'remote_addr' => $remoteAddr,
            ]);
        }

        return null;
    }

    private function assertProxyIpAllowed(?string $remoteAddr): void
    {
        throw_unless(
            IpUtils::checkIp($remoteAddr, $this->allowList),
            new ProxyAuthIpNotAllowedException($remoteAddr, $this->allowList),
        );
    }

    private function assertUserHeaderPresent(Request $request, ?string $remoteAddr): void
    {
        throw_unless(
            $request->header($this->userHeader),
            new ProxyAuthUserHeaderMissingException($this->userHeader, $remoteAddr),
        );
    }
}
