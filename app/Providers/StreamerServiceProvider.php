<?php

namespace App\Providers;

use App\Services\Streamers\LocalStreamer;
use App\Services\Streamers\PhpStreamer;
use App\Services\Streamers\XAccelRedirectStreamer;
use App\Services\Streamers\XSendFileStreamer;
use Illuminate\Support\ServiceProvider;

class StreamerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LocalStreamer::class, static function (): LocalStreamer {
            return match (config('koel.streaming.method')) {
                'x-sendfile' => new XSendFileStreamer(),
                'x-accel-redirect' => new XAccelRedirectStreamer(),
                default => new PhpStreamer(),
            };
        });
    }
}
