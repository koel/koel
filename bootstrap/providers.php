<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\BroadcastServiceProvider;
use App\Providers\DownloadServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\ITunesServiceProvider;
use App\Providers\LicenseServiceProvider;
use App\Providers\MacroProvider;
use App\Providers\ObjectStorageServiceProvider;
use App\Providers\SongStorageServiceProvider;
use App\Providers\StreamerServiceProvider;
use App\Providers\UtilServiceProvider;
use App\Providers\YouTubeServiceProvider;
use Intervention\Image\ImageServiceProvider;
use Jackiedo\DotenvEditor\DotenvEditorServiceProvider;
use Laravel\Scout\ScoutServiceProvider;
use OwenIt\Auditing\AuditingServiceProvider;
use TeamTNT\Scout\TNTSearchScoutServiceProvider;

return [
    DotenvEditorServiceProvider::class,
    ImageServiceProvider::class,

    ScoutServiceProvider::class,
    AuditingServiceProvider::class,
    TNTSearchScoutServiceProvider::class,

    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    UtilServiceProvider::class,
    YouTubeServiceProvider::class,
    DownloadServiceProvider::class,
    BroadcastServiceProvider::class,
    ITunesServiceProvider::class,
    StreamerServiceProvider::class,
    SongStorageServiceProvider::class,
    ObjectStorageServiceProvider::class,
    MacroProvider::class,
    LicenseServiceProvider::class,
];
