<?php

namespace App\Http\Streamers;

use App\Models\Setting;

class XAccelRedirectStreamer extends BaseStreamer implements StreamerInterface
{
    public function __construct($id)
    {
        parent::__construct($id);
    }

    /**
     * Stream the current song using nginx's X-Accel-Redirect.
     */
    public function stream()
    {
        header('X-Accel-Redirect: '.str_replace(Setting::get('media_path'), '/media/', $this->song->path));
        header("Content-Type: {$this->contentType}");
        header('Content-Disposition: inline; filename="'.basename($this->song->path).'"');

        exit;
    }
}
