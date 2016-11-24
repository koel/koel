<?php

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as IlluminateTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends IlluminateTestCase
{
    protected $mediaPath;
    protected $coverPath;

    public function __construct()
    {
        parent::__construct();

        $this->mediaPath = __DIR__.'/songs';
    }

    public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
    }

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        $this->coverPath = $app->basePath().'/public/img/covers';

        return $app;
    }

    private function prepareForTests()
    {
        Artisan::call('migrate');

        if (!User::all()->count()) {
            Artisan::call('db:seed');
        }

        if (!file_exists($this->coverPath)) {
            mkdir($this->coverPath, 0777, true);
        }
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
