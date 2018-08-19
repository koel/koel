<?php

namespace App\Services;


interface ApiConsumerInterface
{
    /** @return string */
    public function getEndpoint();

    /** @return string */
    public function getKey();

    /** @return string|null */
    public function getSecret();
}
