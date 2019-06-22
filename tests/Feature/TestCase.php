<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;
use Mockery;
use Tests\CreatesApplication;
use Tests\Traits\InteractsWithIoc;
use Tymon\JWTAuth\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseTransactions, InteractsWithIoc;

    /** @var JWTAuth */
    private $auth;

    public function setUp()
    {
        parent::setUp();

        $this->auth = app(JWTAuth::class);

        $this->prepareForTests();
    }

    /**
     * Create a sample media set, with a complete artist+album+song trio.
     *
     * @throws Exception
     */
    protected function createSampleMediaSet(): void
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

    protected function getAsUser($url, $user = null): self
    {
        return $this->get($url, [
            'Authorization' => 'Bearer '.$this->generateJwtToken($user),
        ]);
    }

    protected function deleteAsUser($url, $data = [], $user = null): self
    {
        return $this->delete($url, $data, [
            'Authorization' => 'Bearer '.$this->generateJwtToken($user),
        ]);
    }

    protected function postAsUser($url, $data, $user = null): self
    {
        return $this->post($url, $data, [
            'Authorization' => 'Bearer '.$this->generateJwtToken($user),
        ]);
    }

    protected function putAsUser($url, $data, $user = null): self
    {
        return $this->put($url, $data, [
            'Authorization' => 'Bearer '.$this->generateJwtToken($user),
        ]);
    }

    private function generateJwtToken(?User $user): string
    {
        return $this->auth->fromUser($user ?: factory(User::class)->create());
    }

    protected function tearDown()
    {
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());

        Mockery::close();
        parent::tearDown();
    }
}
