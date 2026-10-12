<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Image Driver
     |--------------------------------------------------------------------------
     |
     | Laravel's image manipulation features support "GD Library" and "Imagick"
     | to process images internally. GD is used by default; set this to
     | "imagick" to use Imagick instead.
     |
     | Supported: "gd", "imagick"
     |
     */

    'default' => env('IMAGE_DRIVER', 'gd'),
];
