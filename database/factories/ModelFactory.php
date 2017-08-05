<?php

$factory->define(App\Models\User::class, function ($faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
        'password' => bcrypt(str_random(10)),
        'is_admin' => false,
        'preferences' => [],
        'remember_token' => str_random(10),
    ];
});

$factory->defineAs(App\Models\User::class, 'admin', function ($faker) use ($factory) {
    $user = $factory->raw(App\Models\User::class);

    return array_merge($user, ['is_admin' => true]);
});

$factory->define(App\Models\Artist::class, function ($faker) {
    return [
        'name' => $faker->name,
        'image' => md5(uniqid()).'.jpg',
    ];
});

$factory->define(App\Models\Album::class, function ($faker) {
    return [
        'artist_id' => factory(\App\Models\Artist::class)->create()->id,
        'name' => ucwords($faker->words(random_int(2, 5), true)),
        'cover' => md5(uniqid()).'.jpg',
    ];
});

$factory->define(App\Models\Song::class, function ($faker) {
    $album = factory(\App\Models\Album::class)->create();

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

$factory->define(App\Models\Playlist::class, function ($faker) {
    return [
        'name' => $faker->name,
    ];
});

$factory->define(\App\Models\Interaction::class, function ($faker) {
    return [
        'song_id' => factory(\App\Models\Song::class)->create()->id,
        'user_id' => factory(\App\Models\User::class)->create()->id,
        'liked' => $faker->boolean,
        'play_count' => $faker->randomNumber,
    ];
});

$factory->define(\App\Models\Setting::class, function ($faker) {
    return [
        'key' => $faker->slug,
        'value' => $faker->name,
    ];
});
