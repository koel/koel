<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Credentials
    |--------------------------------------------------------------------------
    |
    | When running `php artisan koel:init` the admin is set using the .env
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'media_path' => env('MEDIA_PATH'),

    // The *relative* path to the directory to store album covers and thumbnails, *with* a trailing slash.
    'album_cover_dir' => 'img/covers/',

    // The *relative* path to the directory to store artist images, *with* a trailing slash.
    'artist_image_dir' => 'img/artists/',

    /*
    |--------------------------------------------------------------------------
    | Sync Options
    |--------------------------------------------------------------------------
    |
    | A timeout is set when using the browser to scan the folder path
    |
    */

    'sync' => [
        'timeout' => env('APP_MAX_SCAN_TIME', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Streaming Configurations
    |--------------------------------------------------------------------------
    |
    | Many streaming options can be set, including, 'bitrate' with 128 set
    | as the default, 'method' with php as the default and 'transcoding'
    | to configure the path for FFMPEG to transcode FLAC audio files
    |
    */

    'streaming' => [
        'bitrate' => env('OUTPUT_BIT_RATE', 128),
        'method' => env('STREAMING_METHOD'),
        'ffmpeg_path' => env('FFMPEG_PATH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Youtube Integration
    |--------------------------------------------------------------------------
    |
    | Youtube integration requires an youtube API key, see wiki for more
    |
    */

    'youtube' => [
        'key' => env('YOUTUBE_API_KEY'),
        'endpoint' => 'https://www.googleapis.com/youtube/v3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Last.FM Integration
    |--------------------------------------------------------------------------
    |
    | See wiki on how to integrate with Last.FM
    |
    */

    'lastfm' => [
        'key' => env('LASTFM_API_KEY'),
        'secret' => env('LASTFM_API_SECRET'),
        'endpoint' => 'https://ws.audioscrobbler.com/2.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN
    |--------------------------------------------------------------------------
    |
    |
    |
    */

    'cdn' => [
        'url' => env('CDN_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Downloading Music
    |--------------------------------------------------------------------------
    |
    | Koel provides the ability to prohibit or allow [default] downloading music
    |
    */

    'download' => [
        'allow' => env('ALLOW_DOWNLOAD', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Dot Files
    |--------------------------------------------------------------------------
    |
    | Ignore dot files and folders when scanning for media files.
    |
    */
    'ignore_dot_files' => env('IGNORE_DOT_FILES', true),

    'itunes' => [
        'enabled' => env('USE_ITUNES', true),
        'affiliate_id' => '1000lsGu',
        'endpoint' => 'https://itunes.apple.com/search',
    ],

    'cache_media' => env('CACHE_MEDIA', true),

    'memory_limit' => env('MEMORY_LIMIT'),

    'force_https' => env('FORCE_HTTPS', false),

    'misc' => [
        'home_url' => 'https://koel.dev',
        'docs_url' => 'https://docs.koel.dev',
        'sponsor_github_url' => 'https://github.com/users/phanan/sponsorship',
        'sponsor_open_collective_url' => 'https://opencollective.com/koel',
        'demo' => env('KOEL_DEMO', false),
    ],
];
