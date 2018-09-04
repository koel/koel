<?php

namespace Tests\Unit\Services;

use App\Services\LastfmService;
use Mockery;
use Mockery\Mock;
use Tests\TestCase;

class LastfmServiceTest extends TestCase
{
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
}
