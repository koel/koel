<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\LastfmService;
use App\Services\UserPreferenceService;
use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
use Mockery;
use Mockery\Mock;
use Mockery\MockInterface;
use Tests\TestCase;

class LastfmServiceTest extends TestCase
{
    /** @var Client */
    private $client;

    /** @var Cache */
    private $cache;

    /** @var Logger */
    private $logger;

    /** @var UserPreferenceService|MockInterface */
    private $userPreferenceService;

    /** @var LastfmService */
    private $lastfmService;

    public function setUp()
    {
        parent::setUp();

        $this->client = Mockery::mock(Client::class);
        $this->cache = Mockery::mock(Cache::class);
        $this->logger = Mockery::mock(Logger::class);
        $this->userPreferenceService = Mockery::mock(UserPreferenceService::class);
        $this->lastfmService = new LastfmService(
            $this->client,
            $this->cache,
            $this->logger,
            $this->userPreferenceService
        );
    }

    public function testBuildAuthCallParams(): void
    {
        /** @var Mock|LastfmService $lastfm */
        $lastfm = Mockery::mock(LastfmService::class)->makePartial();
        $lastfm->shouldReceive('getKey')->andReturn('key');
        $lastfm->shouldReceive('getSecret')->andReturn('secret');

        $params = [
            'qux' => '安',
            'bar' => 'baz',
        ];

        // When I build Last.fm-compatible API parameters using the raw parameters
        $builtParams = $lastfm->buildAuthCallParams($params);
        $builtParamsAsString = $lastfm->buildAuthCallParams($params, true);

        // Then I receive the Last.fm-compatible API parameters
        $this->assertEquals([
            'api_key' => 'key',
            'bar' => 'baz',
            'qux' => '安',
            'api_sig' => '7f21233b54edea994aa0f23cf55f18a2',
        ], $builtParams);

        // And the string version as well
        $this->assertEquals(
            'api_key=key&bar=baz&qux=安&api_sig=7f21233b54edea994aa0f23cf55f18a2',
            $builtParamsAsString
        );
    }

    public function testGetUserSessionKey(): void
    {
        /** @var User $user */
        $user = Mockery::mock(User::class);

        $this->userPreferenceService->shouldReceive('get')
            ->with($user, 'lastfm_session_key')
            ->andReturn('foo');

        self::assertSame('foo', $this->lastfmService->getUserSessionKey($user));
    }

    public function testSetUserSessionKey(): void
    {
        /** @var User $user */
        $user = Mockery::mock(User::class);

        $this->userPreferenceService->shouldReceive('set')
            ->with($user, 'lastfm_session_key', 'foo');

        $this->lastfmService->setUserSessionKey($user, 'foo');
    }

    public function testDeleteUserSessionKey(): void
    {
        /** @var User $user */
        $user = Mockery::mock(User::class);

        $this->userPreferenceService->shouldReceive('delete')
            ->with($user, 'lastfm_session_key');

        $this->lastfmService->deleteUserSessionKey($user);
    }
}
