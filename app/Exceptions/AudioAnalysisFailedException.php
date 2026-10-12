<?php

namespace App\Exceptions;

use RuntimeException;

class AudioAnalysisFailedException extends RuntimeException
{
    public function __construct(string $errorOutput = '')
    {
        parent::__construct('Audio analysis failed' . ($errorOutput ? ": $errorOutput" : '.'));
    }
}
