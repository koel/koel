<?php

namespace App\Jobs;

use App\Services\MediaScanner;
use App\Values\ScanConfiguration;
use App\Values\ScanResultCollection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanSongsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private array $songs;
    private ScanConfiguration $config;

    public function __construct(array $songs, ScanConfiguration $config)
    {
        $this->songs = $songs;
        $this->config = $config;
    }

    public function handle(MediaScanner $scanner): void
    {
        $results = ScanResultCollection::create();
        $results = $scanner->processSongs($this->songs, $this->config, $results);
        $scanner->dispatchCompletedEvents($results);
    }
}
