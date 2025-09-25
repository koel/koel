<?php

use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Resolvers\IpAddressResolver;
use OwenIt\Auditing\Resolvers\UrlResolver;
use OwenIt\Auditing\Resolvers\UserAgentResolver;
use OwenIt\Auditing\Resolvers\UserResolver;

return [

    'enabled' => env('AUDITING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Audit Implementation
    |--------------------------------------------------------------------------
    |
    | Define which Audit model implementation should be used.
    |
    */

    'implementation' => Audit::class,

    /*
    |--------------------------------------------------------------------------
    | User Morph prefix & Guards
    |--------------------------------------------------------------------------
    |
    | Define the morph prefix and authentication guards for the User resolver.
    |
    */

    'user' => [
        'morph_prefix' => 'user',
        'guards' => [
            'web',
            'api',
        ],
        'resolver' => UserResolver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Resolvers
    |--------------------------------------------------------------------------
    |
    | Define the IP Address, User Agent and URL resolver implementations.
    |
    */
    'resolvers' => [
        'ip_address' => IpAddressResolver::class,
        'user_agent' => UserAgentResolver::class,
        'url' => UrlResolver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Events
    |--------------------------------------------------------------------------
    |
    | The Eloquent events that trigger an Audit.
    |
    */

    'events' => [
        'created',
        'updated',
        'deleted',
        'restored',
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | Enable the strict mode when auditing?
    |
    */

    'strict' => false,

    /*
    |--------------------------------------------------------------------------
    | Global exclude
    |--------------------------------------------------------------------------
    |
    | Have something you always want to exclude by default? - add it here.
    | Note that this is overwritten (not merged) with local exclude
    |
    */

    'exclude' => [],

    /*
    |--------------------------------------------------------------------------
    | Empty Values
    |--------------------------------------------------------------------------
    |
    | Should Audit records be stored when the recorded old_values & new_values
    | are both empty?
    |
    | Some events may be empty on purpose. Use allowed_empty_values to exclude
    | those from the empty values check. For example when auditing
    | model retrieved events which will never have new and old values.
    |
    |
    */

    'empty_values' => true,
    'allowed_empty_values' => [
        'retrieved',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Array Values
    |--------------------------------------------------------------------------
    |
    | Should the array values be audited?
    |
    | By default, array values are not allowed. This is to prevent performance
    | issues when storing large amounts of data. You can override this by
    | setting allow_array_values to true.
    */
    'allowed_array_values' => false,

    /*
    |--------------------------------------------------------------------------
    | Audit Timestamps
    |--------------------------------------------------------------------------
    |
    | Should the created_at, updated_at and deleted_at timestamps be audited?
    |
    */

    'timestamps' => false,

    /*
    |--------------------------------------------------------------------------
    | Audit Threshold
    |--------------------------------------------------------------------------
    |
    | Specify a threshold for the number of Audit records a model can have.
    | Zero means no limit.
    |
    */

    'threshold' => 0,

    /*
    |--------------------------------------------------------------------------
    | Audit Driver
    |--------------------------------------------------------------------------
    |
    | The default audit driver used to keep track of changes.
    |
    */

    'driver' => 'database',

    /*
    |--------------------------------------------------------------------------
    | Audit Driver Configurations
    |--------------------------------------------------------------------------
    |
    | Available audit drivers and respective configurations.
    |
    */

    'drivers' => [
        'database' => [
            'table' => 'audits',
            'connection' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Queue Configurations
    |--------------------------------------------------------------------------
    |
    | Available audit queue configurations.
    |
    */

    'queue' => [
        'enable' => false,
        'connection' => 'sync',
        'queue' => 'default',
        'delay' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Console
    |--------------------------------------------------------------------------
    |
    | Whether console events should be audited (eg. php artisan db:seed).
    |
    */

    'console' => false,
];
