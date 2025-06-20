<?php

return [
    'storage_driver' => env('STORAGE_DRIVER', 'local') ?: 'local',

    'media_path' => env('MEDIA_PATH'),

    // The absolute path to the directory to store artifacts, such as the transcoded audio files
    // or downloaded podcast episodes. By default, it is set to the system's temporary directory.
    'artifacts_path' => env('ARTIFACTS_PATH') ?: realpath(sys_get_temp_dir() . '/koel'),

    // The *relative* path to the directory to store album covers and thumbnails, *with* a trailing slash.
    'album_cover_dir' => 'img/covers/',

    // The *relative* path to the directory to store artist images, *with* a trailing slash.
    'artist_image_dir' => 'img/artists/',

    // The *relative* path to the directory to store playlist covers, *with* a trailing slash.
    'playlist_cover_dir' => 'img/playlists/',

    // The *relative* path to the directory to store user avatars, *with* a trailing slash.
    'user_avatar_dir' => 'img/avatars/',

    /*
    |--------------------------------------------------------------------------
    | Sync Options
    |--------------------------------------------------------------------------
    |
    | A timeout is set when using the browser to scan the folder path
    |
    */

    'scan' => [
        'timeout' => env('APP_MAX_SCAN_TIME', 600),
        'memory_limit' => env('MEMORY_LIMIT'),
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
        'transcode_flac' => env('TRANSCODE_FLAC', true),
        'supported_mime_types' => [
            // Lossy formats
            'audio/mpeg',            // MP3
            'audio/mp4',             // AAC, M4A (MP4 audio)
            'audio/aac',             // AAC
            'audio/ogg',             // Ogg (Vorbis, Opus, Speex, FLAC)
            'audio/vorbis',          // Ogg Vorbis
            'audio/opus',            // Opus
            'audio/flac',            // FLAC
            'audio/x-flac',          // FLAC (alternate)
            'audio/amr',             // AMR
            'audio/ac3',             // Dolby AC-3
            'audio/dts',             // DTS
            'audio/vnd.rn-realaudio', // RealAudio
            'audio/x-ms-wma',        // Windows Media Audio (WMA)
            'audio/basic',           // µ-law

            // Lossless and other audio formats
            'audio/vnd.wave',        // WAV
            'audio/x-wav',           // WAV (alternate)
            'audio/aiff',            // AIFF
            'audio/x-aiff',          // AIFF (alternate)
            'audio/x-m4a',           // Apple MPEG-4 Audio
            'audio/x-matroska',      // Matroska Audio
            'audio/webm',            // WebM Audio
            'audio/x-ape',           // Monkey’s Audio (APE)
            'audio/tta',             // True Audio (TTA)
            'audio/x-wavpack',       // WavPack
            'audio/x-optimfrog',     // OptimFROG
            'audio/x-shorten',       // Shorten
            'audio/x-lpac',          // LPAC
            'audio/x-dsd',           // DSD (DSF)
            'audio/x-speex',         // Speex
            'audio/x-dss',           // DSS (Digital Speech Standard)
            'audio/x-audible',       // Audible
            'audio/x-twinvq',        // TwinVQ
            'audio/vqf',             // TwinVQ (alternate)
            'audio/x-musepack',      // Musepack
            'audio/x-monkeys-audio',// APE (alternate)
            'audio/x-voc',           // Creative VOC
        ],
        'transcode_required_mime_types' => [
            'audio/vorbis',
            'audio/x-flac',
            'audio/amr',
            'audio/ac3',
            'audio/dts',
            'audio/vnd.rn-realaudio',
            'audio/x-ms-wma',
            'audio/basic',
            'audio/vnd.wave',        // not always handled correctly
            'audio/aiff',
            'audio/x-aiff',
            'audio/x-m4a',           // only if it contains ALAC (not AAC)
            'audio/x-matroska',
            'audio/x-ape',
            'audio/tta',
            'audio/x-wavpack',
            'audio/x-optimfrog',
            'audio/x-shorten',
            'audio/x-lpac',
            'audio/x-dsd',
            'audio/x-speex',
            'audio/x-dss',
            'audio/x-audible',
            'audio/x-twinvq',
            'audio/vqf',
            'audio/x-musepack',
            'audio/x-monkeys-audio',
            'audio/x-voc',
        ],
    ],

    'services' => [
        'youtube' => [
            'key' => env('YOUTUBE_API_KEY'),
            'endpoint' => 'https://www.googleapis.com/youtube/v3',
        ],
        'lastfm' => [
            'key' => env('LASTFM_API_KEY'),
            'secret' => env('LASTFM_API_SECRET'),
            'endpoint' => 'https://ws.audioscrobbler.com/2.0',
        ],
        'spotify' => [
            'client_id' => env('SPOTIFY_CLIENT_ID'),
            'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
        ],
        'itunes' => [
            'enabled' => env('USE_ITUNES', true),
            'affiliate_id' => '1000lsGu',
            'endpoint' => 'https://itunes.apple.com/search',
        ],
    ],

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

    'media_browser' => [
        'enabled' => env('MEDIA_BROWSER_ENABLED', false),
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

    'force_https' => env('FORCE_HTTPS', false),
    'backup_on_delete' => env('BACKUP_ON_DELETE', true),

    'sync_log_level' => env('SYNC_LOG_LEVEL', 'error'),

    'proxy_auth' => [
        'enabled' => env('PROXY_AUTH_ENABLED', false),
        'user_header' => env('PROXY_AUTH_USER_HEADER', 'remote-user'),
        'preferred_name_header' => env('PROXY_AUTH_PREFERRED_NAME_HEADER', 'remote-preferred-name'),
        'allow_list' => array_map(static fn ($entry) => trim($entry), explode(',', env('PROXY_AUTH_ALLOW_LIST', ''))),
    ],

    'misc' => [
        'home_url' => 'https://koel.dev',
        'docs_url' => 'https://docs.koel.dev',
        'sponsor_github_url' => 'https://github.com/users/phanan/sponsorship',
        'sponsor_open_collective_url' => 'https://opencollective.com/koel',
        'demo' => env('KOEL_DEMO', false),
    ],
];
