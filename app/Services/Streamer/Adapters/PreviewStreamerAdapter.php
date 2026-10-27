<?php

namespace App\Services\Streamer\Adapters;

use App\Http\Responses\StreamedFileResponse;
use App\Models\Song;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Services\Transcoding\TranscodeStrategyFactory;
use App\Values\RequestedStreamingConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PreviewStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): StreamedFileResponse|RedirectResponse
    {
        $previewLocation = TranscodeStrategyFactory::make($song->storage)->getPreviewLocation($song);

        if (Str::startsWith($previewLocation, ['http://', 'https://'])) {
            return response()->redirectTo($previewLocation);
        }

        return self::streamLocalPath($previewLocation);
    }
}
