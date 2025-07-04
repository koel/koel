<?php

namespace App\Facades;

use App\Jobs\QueuedJob;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed dispatch(QueuedJob $job)
 * @see \App\Services\Dispatcher
 */
class Dispatcher extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Dispatcher';
    }
}
