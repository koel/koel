<?php

namespace App\Services\Geolocation\Contracts;

interface GeolocationService
{
    public function getCountryCodeFromIp(string $ip): ?string;
}
