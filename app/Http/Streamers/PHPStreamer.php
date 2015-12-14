<?php

namespace App\Http\Streamers;

class PHPStreamer extends BaseStreamer implements StreamerInterface
{
    public function __construct($id)
    {
        parent::__construct($id);
    }

    /**
     * Stream the current song using the most basic PHP method: readfile()
     * Credits: DaveRandom @ http://stackoverflow.com/a/4451376/794641.
     */
    public function stream()
    {
        // Get the 'Range' header if one was sent
        if (isset($_SERVER['HTTP_RANGE'])) {
            // IIS/Some Apache versions
            $range = $_SERVER['HTTP_RANGE'];
        } elseif (function_exists('apache_request_headers') && $apache = apache_request_headers()) {
            // Try Apache again
            $headers = [];

            foreach ($apache as $header => $val) {
                $headers[strtolower($header)] = $val;
            }

            $range = isset($headers['range']) ? $headers['range'] : false;
        } else {
            // We can't get the header/there isn't one set
            $range = false;
        }

        // Get the data range requested (if any)
        $fileSize = filesize($this->song->path);

        if ($range) {
            $partial = true;
            list($param, $range) = explode('=', $range);

            if (strtolower(trim($param)) != 'bytes') {
                // Bad request - range unit is not 'bytes'
                abort(400);
            }

            $range = explode(',', $range);
            $range = explode('-', $range[0]); // We only deal with the first requested range

            if (count($range) != 2) {
                // Bad request - 'bytes' parameter is not valid
                abort(400);
            }

            if ($range[0] === '') {
                // First number missing, return last $range[1] bytes
                $end = $fileSize - 1;
                $start = $end - intval($range[0]);
            } elseif ($range[1] === '') {
                // Second number missing, return from byte $range[0] to end
                $start = intval($range[0]);
                $end = $fileSize - 1;
            } else {
                // Both numbers present, return specific range
                $start = intval($range[0]);
                $end = intval($range[1]);

                if ($end >= $fileSize || (!$start && (!$end || $end == ($fileSize - 1)))) {
                    // Invalid range/whole file specified, return whole file
                    $partial = false;
                }
            }

            $length = $end - $start + 1;
        } else {
            // No range requested
            $partial = false;
        }

        // Send standard headers
        header("Content-Type: {$this->contentType}");
        header("Content-Length: $length");
        header('Content-Disposition: attachment; filename="'.basename($this->song->path).'"');
        header('Accept-Ranges: bytes');

        // if requested, send extra headers and part of file...
        if ($partial) {
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$fileSize");

            if (!$fp = fopen($this->song->path, 'r')) {
                // Error out if we can't read the file
                abort(500);
            }

            if ($start) {
                fseek($fp, $start);
            }

            while ($length) {
                // Read in blocks of 8KB so we don't chew up memory on the server
                $read = ($length > 8192) ? 8192 : $length;
                $length -= $read;
                echo fread($fp, $read);
            }

            fclose($fp);
        } else {
            // ...otherwise just send the whole file
            readfile($this->song->path);
        }

        exit;
    }
}
