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
        'name' => ucwords($faker->words(random_int(2, 5), true)),
        'cover' => md5(uniqid()).'.jpg',
        'year' => $faker->randomNumber(4),
    ];
});

$factory->define(App\Models\Genre::class, function ($faker) {
    return [
        'name' => $faker->name,
        'image' => md5(uniqid()).'.jpg',
    ];
});

$factory->define(App\Models\Song::class, function ($faker) {
    return [
        'title' => ucwords($faker->words(random_int(2, 5), true)),
        'length' => $faker->randomFloat(2, 10, 500),
        'track' => $faker->randomNumber(),
        'disc' => $faker->randomNumber(),
        'genre_id' => random_int(1, 2),
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
