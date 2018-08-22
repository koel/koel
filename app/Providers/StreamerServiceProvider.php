<?php

namespace App\Providers;

use App\Services\Streamers\DirectStreamerInterface;
use App\Services\Streamers\ObjectStorageStreamerInterface;
use App\Services\Streamers\PHPStreamer;
use App\Services\Streamers\S3Streamer;
use App\Services\Streamers\TranscodingStreamer;
use App\Services\Streamers\TranscodingStreamerInterface;
use App\Services\Streamers\XAccelRedirectStreamer;
use App\Services\Streamers\XSendFileStreamer;
use Illuminate\Support\ServiceProvider;

class StreamerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(DirectStreamerInterface::class, static function () {
            switch (config('koel.streaming.method')) {
                case 'x-sendfile':
                    return new XSendFileStreamer();
                case 'x-accel-redirect':
                    return new XAccelRedirectStreamer();
                default:
                    return new PHPStreamer();
            }
        });

        $this->app->bind(TranscodingStreamerInterface::class, TranscodingStreamer::class);
        $this->app->bind(ObjectStorageStreamerInterface::class, S3Streamer::class);
    }
}
