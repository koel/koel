<?php

namespace App\Observers;

use App\Models\RadioStation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class RadioStationObserver
{
    public function creating(RadioStation $radioStation): void
    {
        $this->extractStreamHost($radioStation);
    }

    public function created(RadioStation $radioStation): void
    {
        $this->clearStreamHostsCache();
    }

    public function updating(RadioStation $radioStation): void
    {
        if ($radioStation->isDirty('url')) {
            $this->extractStreamHost($radioStation);
        }

        if (!$radioStation->isDirty('logo')) {
            return;
        }

        rescue_if(
            $radioStation->getRawOriginal('logo'),
            static function (string $oldLogo): void {
                File::delete(image_storage_path($oldLogo));
            }
        );
    }

    public function updated(RadioStation $radioStation): void
    {
        if ($radioStation->wasChanged('stream_host')) {
            $this->clearStreamHostsCache();
        }
    }

    public function deleted(RadioStation $radioStation): void
    {
        rescue_if($radioStation->logo, static fn () => File::delete(image_storage_path($radioStation->logo)));
        $this->clearStreamHostsCache();
    }

    private function extractStreamHost(RadioStation $radioStation): void
    {
        $parsedUrl = parse_url($radioStation->url);
        $host = $parsedUrl['host'] ?? null;
        $port = $parsedUrl['port'] ?? null;
        $scheme = $parsedUrl['scheme'] ?? 'http';

        if ($host) {
            $streamHost = $scheme . '://' . $host;
            if ($port && !in_array($port, [80, 443], true)) {
                $streamHost .= ':' . $port;
            }

            $radioStation->stream_host = $streamHost;
        }
    }

    private function clearStreamHostsCache(): void
    {
        // Clear both old and new cache keys for compatibility
        Cache::forget('radio_station_stream_hosts');
        Cache::forget('radio_station_stream_hosts_v2');
    }
}
