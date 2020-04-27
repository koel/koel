<?php

use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Setting;
use App\Models\Song;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(User::class, function ($faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
        'password' => bcrypt(str_random(10)),
        'is_admin' => false,
        'preferences' => [],
        'remember_token' => str_random(10),
    ];
});

$factory->defineAs(User::class, 'admin', function () use ($factory) {
    $user = $factory->raw(User::class);

    return array_merge($user, ['is_admin' => true]);
});

$factory->define(Artist::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'image' => md5(uniqid()).'.jpg',
    ];
});

$factory->define(Album::class, function (Faker $faker) {
    return [
        'artist_id' => factory(Artist::class)->create()->id,
        'name' => ucwords($faker->words(random_int(2, 5), true)),
        'cover' => md5(uniqid()).'.jpg',
    ];
});

$factory->define(Song::class, function (Faker $faker) {
    /** @var Album $album */
    $album = factory(Album::class)->create();

    return [
        'album_id' => $album->id,
        'artist_id' => $album->artist->id,
        'title' => ucwords($faker->words(random_int(2, 5), true)),
        'length' => $faker->randomFloat(2, 10, 500),
        'track' => random_int(1, 20),
        'lyrics' => $faker->paragraph(),
        'path' => '/tmp/'.uniqid().'.mp3',
        'mtime' => time(),
    ];
});

$factory->define(Playlist::class, function (Faker $faker) {
    return [
        'user_id' => static function (): int {
            throw new InvalidArgumentException('A user_id must be supplied');
        },
        'name' => $faker->name,
        'rules' => null,
    ];
});

$factory->define(Interaction::class, function (Faker $faker) {
    return [
        'song_id' => factory(Song::class)->create()->id,
        'user_id' => factory(User::class)->create()->id,
        'liked' => $faker->boolean,
        'play_count' => $faker->randomNumber,
    ];
});

$factory->define(Setting::class, function (Faker $faker) {
    return [
        'key' => $faker->slug,
        'value' => $faker->name,
    ];
});
