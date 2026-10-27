<?php

namespace App\Http\Controllers;

use App\Models\Embed;
use App\Models\Song;
use App\Services\Streamer\Adapters\PreviewStreamerAdapter;
use App\Services\Streamer\Streamer;

class StreamEmbedController extends Controller
{
    public function __construct(
        private readonly PreviewStreamerAdapter $previewStreamerAdapter,
    ) {}

    public function __invoke(Embed $embed, Song $song)
    {
        $adapter = $song->preview ? $this->previewStreamerAdapter : null;

        return (new Streamer(song: $song, adapter: $adapter))->stream();
    }
}
