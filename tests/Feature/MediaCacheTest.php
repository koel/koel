<?php

namespace Tests\Feature;

use Cache;
use MediaCache;
use Tests\BrowserKitTestCase;

class MediaCacheTest extends BrowserKitTestCase
{
    public function testGetCache()
    {
        // first clear all cache
        Cache::flush();
        // now make sure the get() function returns data
        $data = MediaCache::get();
        $this->assertNotNull($data);
        // and the data are seen in the cache as well
        $this->assertEquals($data, Cache::get('media_cache'));
    }
}
