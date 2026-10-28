<?php

namespace App\Http\Controllers;

use App\Models\Embed;
use App\Models\Song;
use App\Services\Streamer\Adapters\PreviewStreamerAdapter;
use App\Services\Streamer\Streamer;
use App\Values\EmbedOptions;
use Illuminate\Http\Request;

class StreamEmbedController extends Controller
{
    public function __construct(
        private readonly PreviewStreamerAdapter $previewStreamerAdapter,
    ) {}

    public function __invoke(Request $request, Embed $embed, Song $song)
    {
        $adapter = $song->preview && EmbedOptions::fromRequest($request)->preview
            ? $this->previewStreamerAdapter
            : null;

        return (new Streamer(song: $song, adapter: $adapter))->stream();
    }
}
