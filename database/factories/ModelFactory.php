<?php

$factory->define(App\Models\User::class, function ($faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
        'password' => bcrypt(str_random(10)),
        'is_admin' => false,
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
    ];
});

$factory->define(App\Models\Album::class, function ($faker) {
    return [
        'name' => $faker->sentence,
        'cover' => md5(uniqid()).'.jpg',
    ];
});

$factory->define(App\Models\Song::class, function ($faker) {
    return [
        'title' => $faker->sentence,
        'length' => $faker->randomFloat(2, 10, 500),
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
