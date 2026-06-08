<?php

namespace Tests\Unit\Services\Auth;

use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidTwoFactorLoginTokenException;
use App\Exceptions\RequiresTwoFactorException;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\TokenManager;
use App\Services\Auth\TwoFactorAuthenticator;
use App\Values\CompositeToken;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class AuthenticationServiceTest extends TestCase
{
    private UserRepository|MockInterface $userRepository;
    private TokenManager|MockInterface $tokenManager;
    private PasswordBroker|MockInterface $passwordBroker;
    private TwoFactorAuthenticator|MockInterface $twoFactorAuth;
    private AuthenticationService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->mock(UserRepository::class);
        $this->tokenManager = $this->mock(TokenManager::class);
        $this->passwordBroker = $this->mock(PasswordBroker::class);
        $this->twoFactorAuth = $this->mock(TwoFactorAuthenticator::class);

        $this->service = new AuthenticationService(
            $this->userRepository,
            $this->tokenManager,
            $this->passwordBroker,
            $this->twoFactorAuth,
        );
    }

    #[Test]
    public function trySendResetPasswordLink(): void
    {
        $this->passwordBroker
            ->expects('sendResetLink')
            ->with(['email' => 'foo@bar.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->assertTrue($this->service->trySendResetPasswordLink('foo@bar.com'));
    }

    #[Test]
    public function loginWithTwoFactorEnabledUserThrowsRequiresTwoFactor(): void
    {
        $user = create_user();
        $user->two_factor_confirmed_at = now();
        $user->save();

        $this->userRepository->expects('findFirstWhere')->with('email', $user->email)->andReturn($user);

        $this->expectException(RequiresTwoFactorException::class);

        $this->service->login($user->email, 'secret');
    }

    #[Test]
    public function generateTwoFactorLoginTokenStashesEncryptedUserIdInCache(): void
    {
        $user = create_user();

        $token = $this->service->generateTwoFactorLoginToken($user);

        self::assertMatchesRegularExpression('/^[A-Za-z0-9]{32}$/', $token);

        $cached = Cache::pull(cache_key('two-factor login token', $token));
        self::assertNotNull($cached);
        self::assertSame($user->id, decrypt($cached));
    }

    #[Test]
    public function loginViaTwoFactorChallengeMintsCompositeToken(): void
    {
        $user = create_user();
        $token = 'login-token';
        $cacheKey = cache_key('two-factor login token', $token);
        Cache::set($cacheKey, encrypt($user->id), 300);

        $compositeToken = CompositeToken::fromAccessTokens(
            new NewAccessToken(new PersonalAccessToken(), 'api-token'),
            new NewAccessToken(new PersonalAccessToken(), 'audio-token'),
        );

        $this->userRepository->expects('getOne')->with($user->id)->andReturn($user);
        $this->twoFactorAuth->expects('verify')->with($user, '123456')->andReturnTrue();
        $this->tokenManager->expects('createCompositeToken')->with($user)->andReturn($compositeToken);

        self::assertSame($compositeToken, $this->service->loginViaTwoFactorChallenge($token, '123456'));
        self::assertNull(Cache::get($cacheKey));
    }

    #[Test]
    public function loginViaTwoFactorChallengeThrowsOnMissingToken(): void
    {
        $this->expectException(InvalidTwoFactorLoginTokenException::class);

        $this->service->loginViaTwoFactorChallenge('nonexistent', '123456');
    }

    #[Test]
    public function loginViaTwoFactorChallengeThrowsOnCorruptCiphertext(): void
    {
        $token = 'corrupted-token';
        Cache::set(cache_key('two-factor login token', $token), 'not-valid-ciphertext', 300);

        $this->expectException(InvalidTwoFactorLoginTokenException::class);

        $this->service->loginViaTwoFactorChallenge($token, '123456');
    }

    #[Test]
    public function loginViaTwoFactorChallengeRestoresNonceOnWrongCode(): void
    {
        $user = create_user();
        $token = 'login-token';
        $cacheKey = cache_key('two-factor login token', $token);
        Cache::set($cacheKey, encrypt($user->id), 300);

        $this->userRepository->expects('getOne')->with($user->id)->andReturn($user);
        $this->twoFactorAuth->expects('verify')->with($user, 'wrong')->andReturnFalse();

        $caught = null;

        try {
            $this->service->loginViaTwoFactorChallenge($token, 'wrong');
        } catch (InvalidCredentialsException $e) {
            $caught = $e;
        }

        self::assertInstanceOf(InvalidCredentialsException::class, $caught);

        $restored = Cache::pull($cacheKey);
        self::assertNotNull($restored);
        self::assertSame($user->id, decrypt($restored));
    }
}
