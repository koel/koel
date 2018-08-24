<?php

namespace App\Services;

interface ApiConsumerInterface
{
    public function getEndpoint(): string;
    public function getKey(): ?string;
    public function getSecret(): ?string;
}
