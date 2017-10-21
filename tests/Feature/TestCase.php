<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use JWTAuth;
use Laravel\BrowserKitTesting\DatabaseTransactions;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;
use Tests\CreatesApplication;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
    }

    /**
     * Create a sample media set, with a complete artist+album+song trio.
     */
    protected function createSampleMediaSet()
    {
        $artist = factory(Artist::class)->create();

        // Sample 3 albums
        $albums = factory(Album::class, 3)->create([
            'artist_id' => $artist->id,
        ]);

        // 7-15 songs per albums
        foreach ($albums as $album) {
            factory(Song::class, random_int(7, 15))->create([
                'album_id' => $album->id,
                'artist_id' => $artist->id,
            ]);
        }
    }

    protected function getAsUser($url, $user = null)
    {
        if (!$user) {
            $user = factory(User::class)->create();
        }

        return $this->get($url, [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
        ]);
    }

    protected function deleteAsUser($url, $data = [], $user = null)
    {
        if (!$user) {
            $user = factory(User::class)->create();
        }

        return $this->delete($url, $data, [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
        ]);
    }

    protected function postAsUser($url, $data, $user = null)
    {
        if (!$user) {
            $user = factory(User::class)->create();
        }

        return $this->post($url, $data, [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
        ]);
    }

    protected function putAsUser($url, $data, $user = null)
    {
        if (!$user) {
            $user = factory(User::class)->create();
        }

        return $this->put($url, $data, [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
        ]);
    }
}
