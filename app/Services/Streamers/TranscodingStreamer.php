<?php

namespace App\Services\Streamers;

class TranscodingStreamer extends Streamer implements TranscodingStreamerInterface
{
    /**
     * Bit rate the stream should be transcoded at.
     *
     * @var int
     */
    private $bitRate;

    /**
     * Time point to start transcoding from.
     *
     * @var float
     */
    private $startTime;

    /**
     * On-the-fly stream the current song while transcoding.
     */
    public function stream(): void
    {
        $ffmpeg = config('koel.streaming.ffmpeg_path');
	$codec = config('koel.streaming.ffmpeg_codec');
        abort_unless(is_executable($ffmpeg), 500, 'Transcoding requires valid ffmpeg settings.');

	//used the chart on https://en.wikipedia.org/wiki/HTML5_audio#Supported_audio_coding_formats to match up formats to container types.
	$container = null;
	switch($codec)
	{
	   case 'libopus':
	   case 'libvorbis':
	      $container = 'ogg';
	      break;
	   case 'libmp3lame':
	      $container = 'mp3';
	      break;
	   case 'aac':
	      $container = 'adts';
	      break;
	}

        $bitRate = filter_var($this->bitRate, FILTER_SANITIZE_NUMBER_INT);

        header('Content-Type: audio/mpeg');
        header('Content-Disposition: attachment; filename="'.basename($this->song->path).'"');

        $args = [
            '-i '.escapeshellarg($this->song->path),
            '-map 0:0',
            '-v 0',
	    "-b:a {$bitRate}k",
	    "-c:a {$codec}",
            "-f {$container}",
            '-',
        ];

        if ($this->startTime) {
            array_unshift($args, "-ss {$this->startTime}");
        }

        passthru("$ffmpeg ".implode($args, ' '));
    }

    public function setBitRate(int $bitRate): void
    {
        $this->bitRate = $bitRate;
    }

    public function setStartTime(float $startTime): void
    {
        $this->startTime = $startTime;
    }
}
