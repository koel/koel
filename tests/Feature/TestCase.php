<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Mockery;
use ReflectionClass;
use Tests\Traits\CreatesApplication;
use Tests\Traits\InteractsWithIoc;
use Tests\Traits\SandboxesTests;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;
    use InteractsWithIoc;
    use SandboxesTests;

    public function setUp(): void
    {
        parent::setUp();

        $this->prepareForTests();
        self::createSandbox();
    }

    /**
     * Create a sample media set, with a complete artist+album+song trio.
     *
     * @throws Exception
     */
    protected static function createSampleMediaSet(): void
    {
        /** @var Artist $artist */
        $artist = factory(Artist::class)->create();

        // Sample 3 albums
        /** @var Album[] $albums */
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

    private function jsonAsUser(?User $user, string $method, $uri, array $data = [], array $headers = []): TestResponse
    {
        $user = $user ?: factory(User::class)->create();
        $headers['X-Requested-With'] = 'XMLHttpRequest';
        $headers['Authorization'] = 'Bearer '.$user->createToken('koel')->plainTextToken;

        return parent::json($method, $uri, $data, $headers);
    }

    protected function getAsUser(string $url, ?User $user = null): TestResponse
    {
        return $this->jsonAsUser($user, 'get', $url);
    }

    protected function deleteAsUser(string $url, array $data = [], ?User $user = null): TestResponse
    {
        return $this->jsonAsUser($user, 'delete', $url, $data);
    }

    protected function postAsUser(string $url, array $data, ?User $user = null): TestResponse
    {
        return $this->jsonAsUser($user, 'post', $url, $data);
    }

    protected function putAsUser(string $url, array $data, ?User $user = null): TestResponse
    {
        return $this->jsonAsUser($user, 'put', $url, $data);
    }

    protected static function getNonPublicProperty($object, string $property)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    protected function tearDown(): void
    {
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
        Mockery::close();
        self::destroySandbox();

        parent::tearDown();
    }
}
