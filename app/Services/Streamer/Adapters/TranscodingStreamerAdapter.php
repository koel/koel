<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\Transcoding\TranscodeStrategyFactory;
use App\Values\RequestedStreamingConfig;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;

class TranscodingStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function stream(Song $song, ?RequestedStreamingConfig $config = null)
    {
        $ffmpegPath = (string) config('koel.streaming.ffmpeg_path');

        // Fail fast if ffmpeg is misconfigured or missing
        abort_unless(
            is_executable($ffmpegPath),
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'ffmpeg not found or not executable.',
        );

        $bitRate = (int) ($config?->bitRate ?: config('koel.streaming.bitrate'));

        // Prefer the raw original attribute to avoid an extra query; fall back to direct DB read for robustness.
        $dbPath = $song->getRawOriginal('path') ?? Song::where('id', $song->id)->value('path');

        if ($dbPath && is_file($dbPath) && is_readable($dbPath)) {
            return $this->streamLocalFile($dbPath, $song, $bitRate, $ffmpegPath);
        }

        return $this->handleCloudRedirect($song, $bitRate);
    }

    private function streamLocalFile(string $source, Song $song, int $bitRate, string $ffmpegPath): StreamedResponse
    {
        set_time_limit(0);

        $bytesPerSecond = ($bitRate * 1000) / 8;
        $totalBytes = (int) ($song->length * $bytesPerSecond);

        $range = (string) request()->header('Range');
        
        // NOTE: Preserve the client's Range header as-is. Coercing 'bytes=0-1' to 'bytes=0-' changes semantics
        // and can cause incorrect partial responses. If probing requires special handling, handle it outside
        // of this streaming function or use explicit client detection.

        $start = 0;
        $end = $totalBytes > 0 ? $totalBytes - 1 : 0;

        if ($range) {
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = (int) $matches[1];
                if (isset($matches[2]) && $matches[2] !== '') {
                    $end = (int) $matches[2];
                }
            } elseif (preg_match('/bytes=-(\d+)/', $range, $matches)) {
                // Support suffix-range (bytes=-N) where client requests the last N bytes
                $suffix = (int) $matches[1];
                if ($totalBytes > 0) {
                    $start = max(0, $totalBytes - $suffix);
                    $end = $totalBytes - 1;
                }
            }
        }

        $startSecond = $bytesPerSecond > 0 ? $start / $bytesPerSecond : 0;
        $length = $totalBytes > 0 ? max(0, ($end - $start) + 1) : 0;
        
        // Using high precision (6 decimal points) for better seek accuracy
        $startSecondStr = number_format($startSecond, 6, '.', '');

        $ffmpegTpl = '%s -ss %s -i %s -vn -map_metadata -1 -id3v2_version 0 ';
        $ffmpegTpl .= '-c:a libmp3lame -b:a %s -write_xing 0 -f mp3 -';

        $command = sprintf(
            $ffmpegTpl,
            escapeshellarg($ffmpegPath),
            escapeshellarg($startSecondStr), // Consistently escape dynamic arguments
            escapeshellarg($source),
            escapeshellarg("{$bitRate}k")
        );

        $status = ($range && $totalBytes > 0) ? 206 : 200;
        $headers = [
            'Content-Type' => 'audio/mpeg',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Accept-Ranges' => 'bytes',
        ];

        if ($totalBytes > 0) {
            $headers['Content-Length'] = $length;
            if ($range) {
                $headers['Content-Range'] = sprintf('bytes %d-%d/%d', $start, $end, $totalBytes);
            }
        }

        // Use proc_open for reliable process management and error handling.
        // Redirect child's stderr to a null device via descriptor (avoid shell-level redirections).
        return response()->stream(static function () use ($command): void {
            $nullDevice = stripos(PHP_OS, 'WIN') === 0 ? 'NUL' : '/dev/null';
            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['file', $nullDevice, 'w'],
            ];

            $process = proc_open($command, $descriptorspec, $pipes);
            if (!is_resource($process) || !is_array($pipes) || !isset($pipes[1])) {
                if (is_resource($process)) {
                    @proc_close($process);
                }
                return;
            }

            // Fallback: Ensure FFmpeg is killed if PHP shuts down abruptly (e.g. FastCGI disconnect where connection_aborted() fails)
            register_shutdown_function(static function () use ($process): void {
                if (is_resource($process)) {
                   $statusInfo = proc_get_status($process);
                   if ($statusInfo['running']) {
                       @proc_terminate($process, 9); // Force SIGKILL on sudden termination
                   }
                }
            });

            fclose($pipes[0]);
            stream_set_blocking($pipes[1], false);

            try {
                while (true) {
                    // Stop FFmpeg if the client disconnects to avoid orphaned processes
                    if (connection_aborted()) {
                        $statusInfo = proc_get_status($process);
                        if ($statusInfo['running']) {
                            @proc_terminate($process, 15); // SIGTERM
                            usleep(50000); // 50ms wait for graceful exit
                            $statusInfo = proc_get_status($process);
                            if ($statusInfo['running']) {
                                @proc_terminate($process, 9); // SIGKILL
                            }
                        }
                        break;
                    }
                    $data = fread($pipes[1], 8192);
                    $statusInfo = proc_get_status($process);
                    if ($data === false || ($data === '' && !$statusInfo['running'])) break;
                    if ($data === '') { usleep(10000); continue; }
                    
                    echo $data;
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }
            } finally {
                if (isset($pipes) && is_array($pipes)) {
                    foreach ($pipes as $p) { if (is_resource($p)) fclose($p); }
                }
                if (is_resource($process)) {
                    $statusInfo = proc_get_status($process);
                    if ($statusInfo['running']) {
                        @proc_terminate($process, 9);
                    }
                    @proc_close($process);
                }
            }
        }, $status, $headers);
    }

    private function handleCloudRedirect(Song $song, int $bitRate): mixed
    {
        try {
            $location = TranscodeStrategyFactory::make($song->storage)->getTranscodeLocation($song, $bitRate);
            
            if (is_string($location) && Str::startsWith($location, ['http://', 'https://'])) {
                return response()->redirectTo($location);
            }

            // If the strategy returns a local path, stream it instead of returning 404
            if (is_string($location) && is_file($location) && is_readable($location)) {
                // Stream the file to avoid relying on file-size-based headers which may be unstable while transcoding.
                return response()->stream(function () use ($location) {
                    $stream = fopen($location, 'rb');
                    if ($stream === false) {
                        return;
                    }
                    while (!feof($stream)) {
                        $chunk = fread($stream, 8192);
                        if ($chunk === false) {
                            break;
                        }
                        echo $chunk;
                        if (ob_get_level() > 0) ob_flush();
                        flush();
                    }
                    fclose($stream);
                }, 200, [
                    'Content-Type' => mime_content_type($location) ?: 'application/octet-stream',
                    'X-Accel-Buffering' => 'no',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Koel Cloud Error: ' . $e->getMessage(), ['song_id' => $song->id, 'exception' => $e]);
        }
        abort(Response::HTTP_NOT_FOUND, 'File not found.');
    }
}