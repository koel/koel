<?php

namespace App\Providers;

use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\PhpStreamerAdapter;
use App\Services\Streamer\Adapters\XAccelRedirectStreamerAdapter;
use App\Services\Streamer\Adapters\XSendFileStreamerAdapter;
use Illuminate\Support\ServiceProvider;

class StreamerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LocalStreamerAdapter::class, function (): LocalStreamerAdapter {
            return match (config('koel.streaming.method')) {
                'x-sendfile' => $this->app->make(XSendFileStreamerAdapter::class),
                'x-accel-redirect' => $this->app->make(XAccelRedirectStreamerAdapter::class),
                default => $this->app->make(PhpStreamerAdapter::class),
            };
        });
    }
}
