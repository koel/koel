<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Image Driver
     |--------------------------------------------------------------------------
     |
     | Laravel's image manipulation features support "GD Library" and "Imagick"
     | to process images internally. Imagick is preferred when available, as it
     | supports more formats, but you may force either one.
     |
     | Supported: "gd", "imagick"
     |
     */

    'default' => env('IMAGE_DRIVER', extension_loaded('imagick') ? 'imagick' : 'gd'),
];
