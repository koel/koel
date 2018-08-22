<?php

namespace App\Providers;

use App\Factories\StreamerFactory;
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
        $this->app->when(StreamerFactory::class)
            ->needs(DirectStreamerInterface::class)
            ->give(static function () {
                switch (config('koel.streaming.method')) {
                    case 'x-sendfile':
                        return new XSendFileStreamer();
                    case 'x-accel-redirect':
                        return new XAccelRedirectStreamer();
                    default:
                        return new PHPStreamer();
                }
            });

        $this->app->when(StreamerFactory::class)
            ->needs(TranscodingStreamerInterface::class)
            ->give(TranscodingStreamer::class);

        $this->app->when(StreamerFactory::class)
            ->needs(ObjectStorageStreamerInterface::class)
            ->give(S3Streamer::class);
    }
}
