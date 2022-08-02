<?php

namespace App\Providers;

use App\Services\Streamers\DirectStreamerInterface;
use App\Services\Streamers\ObjectStorageStreamerInterface;
use App\Services\Streamers\PhpStreamer;
use App\Services\Streamers\S3Streamer;
use App\Services\Streamers\TranscodingStreamer;
use App\Services\Streamers\TranscodingStreamerInterface;
use App\Services\Streamers\XAccelRedirectStreamer;
use App\Services\Streamers\XSendFileStreamer;
use Illuminate\Support\ServiceProvider;

class StreamerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DirectStreamerInterface::class, static function (): DirectStreamerInterface {
            return match (config('koel.streaming.method')) {
                'x-sendfile' => new XSendFileStreamer(),
                'x-accel-redirect' => new XAccelRedirectStreamer(),
                default => new PhpStreamer(),
            };
        });

        $this->app->bind(TranscodingStreamerInterface::class, TranscodingStreamer::class);
        $this->app->bind(ObjectStorageStreamerInterface::class, S3Streamer::class);
    }
}
