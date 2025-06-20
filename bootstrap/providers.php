<?php

return [
    Jackiedo\DotenvEditor\DotenvEditorServiceProvider::class,
    Intervention\Image\ImageServiceProvider::class,

    Laravel\Scout\ScoutServiceProvider::class,
    OwenIt\Auditing\AuditingServiceProvider::class,
    TeamTNT\Scout\TNTSearchScoutServiceProvider::class,

    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\UtilServiceProvider::class,
    App\Providers\YouTubeServiceProvider::class,
    App\Providers\DownloadServiceProvider::class,
    App\Providers\BroadcastServiceProvider::class,
    App\Providers\ITunesServiceProvider::class,
    App\Providers\StreamerServiceProvider::class,
    App\Providers\SongStorageServiceProvider::class,
    App\Providers\ObjectStorageServiceProvider::class,
    App\Providers\MacroProvider::class,
    App\Providers\LicenseServiceProvider::class,
];
