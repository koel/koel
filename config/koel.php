<?php

return [
    'storage_driver' => env('STORAGE_DRIVER', 'local') ?: 'local',

    'media_path' => env('MEDIA_PATH'),

    // The absolute path to the directory to store artifacts, such as the transcoded audio files
    // or downloaded podcast episodes. By default, it is set to the system's temporary directory.
    'artifacts_path' => env('ARTIFACTS_PATH') ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'koel',

    // The *relative* path to the directory to store artist images, playlist covers, user avatars, etc.
    // This is relative to the public path.
    'image_storage_dir' => 'img/storage/',


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
        'bitrate' => env('TRANSCODE_BIT_RATE') ?: env('OUTPUT_BIT_RATE', 128),
        'method' => env('STREAMING_METHOD'),
        'ffmpeg_path' => env('FFMPEG_PATH') ?: find_ffmpeg_path(),
        'transcode_flac' => env('TRANSCODE_FLAC', true),
        'supported_mime_types' => [
            // Lossy formats
            'audio/mpeg' => 'mp3',            // MP3
            'audio/mp4' => ['mp4', 'm4a'],             // AAC, M4A (MP4 audio)
            'audio/aac' => ['aac'],             // AAC
            'audio/ogg' => 'ogg',             // Ogg (Vorbis, Opus, Speex, FLAC)
            'audio/vorbis' => 'ogg',          // Ogg Vorbis
            'audio/opus' => 'opus',            // Opus
            'audio/flac' => ['flac', 'fla'],            // FLAC
            'audio/x-flac' => ['flac', 'fla'],          // FLAC (alternate)
            'audio/amr' => 'amr',             // AMR
            'audio/ac3' => 'ac3',             // Dolby AC-3
            'audio/dts' => 'dts',             // DTS
            'audio/vnd.rn-realaudio' => ['ra', 'rm'], // RealAudio
            'audio/x-ms-wma' => 'wma',        // Windows Media Audio (WMA)
            'audio/basic' => 'au',           // µ-law

            // Lossless and other audio formats
            'audio/vnd.wave' => 'wav',        // WAV
            'audio/x-wav' => 'wav',           // WAV (alternate)
            'audio/aiff' => ['aif', 'aiff', 'aifc'],            // AIFF
            'audio/x-aiff' => ['aif', 'aiff', 'aifc'],          // AIFF (alternate)
            'audio/x-m4a' => 'mp4',           // Apple MPEG-4 Audio
            'audio/x-matroska' => 'mka',      // Matroska Audio
            'audio/webm' => 'webm',            // WebM Audio
            'audio/x-ape' => 'ape',           // Monkey’s Audio (APE)
            'audio/tta' => 'tta',             // True Audio (TTA)
            'audio/x-wavpack' => ['wv', 'wvc'],       // WavPack
            'audio/x-optimfrog' => ['ofr', 'ofs'],     // OptimFROG
            'audio/x-shorten' => 'shn',       // Shorten
            'audio/x-lpac' => 'lpac',          // LPAC
            'audio/x-dsd' => ['dsf', 'dff'] ,           // DSD (DSF)
            'audio/x-speex' => 'spx',         // Speex
            'audio/x-dss' => 'dss',           // DSS (Digital Speech Standard)
            'audio/x-audible' => 'aa',       // Audible
            'audio/x-twinvq' => 'vqf',        // TwinVQ
            'audio/vqf' => 'vqf',             // TwinVQ (alternate)
            'audio/x-musepack' => ['mpc', 'mp+'],      // Musepack
            'audio/x-monkeys-audio' => 'ape',// APE (alternate)
            'audio/x-voc' => 'voc',           // Creative VOC
        ],
        // Note that this is **not** guaranteed to work 100% of the time, as technically
        // a mime type doesn't tell the actual codec used in the file.
        // However, it's a good enough heuristic for most cases.
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
        'musicbrainz' => [
            'enabled' => env('USE_MUSICBRAINZ', true),
            'endpoint' => 'https://musicbrainz.org/ws/2',
            'user_agent' => env('MUSICBRAINZ_USER_AGENT'),
        ],
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
        'ticketmaster' => [
            'key' => env('TICKETMASTER_API_KEY'),
            'endpoint' => 'https://app.ticketmaster.com/discovery/v2',
            'default_country_code' => env('TICKETMASTER_DEFAULT_COUNTRY_CODE') ?: 'US',
        ],
        'ipinfo' => [
            'token' => env('IPINFO_TOKEN'),
            'endpoint' => 'https://api.ipinfo.io',
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
