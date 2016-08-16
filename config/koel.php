<?php

return [

    'admin'     => [
        'name'  => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
    'password'  => env('ADMIN_PASSWORD'),
    ],

    'sync'      => [
    'timeout'   => env('APP_MAX_SCAN_TIME', 600),
    ],

    'streaming' => [
        'bitrate'   => env('OUTPUT_BIT_RATE', 128),
        'method'    => env('STREAMING_METHOD'),
    'transcoding'   => env('FFMPEG_PATH', '/usr/local/bin/ffmpeg'),
    ],

    'youtube'   => [
        'key'   => env('YOUTUBE_API_KEY'),
    ],

    'lastfm'    => [
        'key'   => env('LASTFM_API_KEY'),
    'secret'    => env('LASTFM_API_SECRET'),
    ],

    'cdn'       => [
        'url'   => env('CDN_URL'),
    ],

    'download'  => [
        'allow' => env('ALLOW_DOWNLOAD', true),
    ],

];
