<?php

namespace App\Exceptions;

use RuntimeException;

class TranscodingFailedException extends RuntimeException
{
    public function __construct(string $errorOutput = '')
    {
        parent::__construct('Transcoding failed' . ($errorOutput ? ": $errorOutput" : '.'));
    }
}
