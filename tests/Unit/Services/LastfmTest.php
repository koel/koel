<?php

namespace Tests\Unit\Services;

use App\Services\Lastfm;
use Tests\TestCase;

class LastfmTest extends TestCase
{
    /** @test */
    public function it_builds_lastfm_compatible_api_parameters()
    {
        // Given there are raw parameters
        $api = new Lastfm('key', 'secret');
        $params = [
            'qux' => '安',
            'bar' => 'baz',
        ];

        // When I build Last.fm-compatible API parameters using the raw parameters
        $builtParams = $api->buildAuthCallParams($params);
        $builtParamsAsString = $api->buildAuthCallParams($params, true);

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
