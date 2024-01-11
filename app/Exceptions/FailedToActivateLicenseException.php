<?php

namespace App\Exceptions;

use Exception;
use GuzzleHttp\Exception\ClientException;
use Throwable;

class FailedToActivateLicenseException extends Exception
{
    public static function fromThrowable(Throwable $e): self
    {
        return new static($e->getMessage(), $e->getCode(), $e);
    }

    public static function fromClientException(ClientException $e): self
    {
        $response = $e->getResponse();

        return new static(json_decode($response->getBody())->error, $response->getStatusCode());
    }
}
