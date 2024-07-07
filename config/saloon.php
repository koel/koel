<?php

declare(strict_types=1);

use Saloon\Http\Senders\GuzzleSender;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Saloon Sender
    |--------------------------------------------------------------------------
    |
    | This value specifies the "sender" class that Saloon should use by
    | default on all connectors. You can change this sender if you
    | would like to use your own. You may also specify your own
    | sender on a per-connector basis.
    |
    */

    'default_sender' => GuzzleSender::class,

    /*
    |--------------------------------------------------------------------------
    | Integrations Path
    |--------------------------------------------------------------------------
    |
    | By default, this package will create any classes within
    | `/app/Http/Integrations` directory. If you're using
    | a different design approach, then your classes
    | may be in a different place. You can change
    | that location so that the saloon:list
    | command will still find your files
    |
    */

    'integrations_path' => base_path('App/Http/Integrations'),
];
