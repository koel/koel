<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Saloon\Http\Response;

final readonly class IpInfoLiteData implements Arrayable
{
    private function __construct(
        public string $ip,
        public string $asn,
        public string $asName,
        public string $asDomain,
        public string $countryCode,
        public string $country,
        public string $continentCode,
        public string $continent,
    ) {
    }

    public static function fromSaloonResponse(Response $response): self
    {
        $json = $response->json();

        return new self(
            ip: $json['ip'] ?? '',
            asn: $json['asn'] ?? '',
            asName: $json['as_name'] ?? '',
            asDomain: $json['as_domain'] ?? '',
            countryCode: $json['country_code'] ?? '',
            country: $json['country'] ?? '',
            continentCode: $json['continent_code'] ?? '',
            continent: $json['continent'] ?? '',
        );
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'asn' => $this->asn,
            'as_name' => $this->asName,
            'as_domain' => $this->asDomain,
            'country_code' => $this->countryCode,
            'country' => $this->country,
            'continent_code' => $this->continentCode,
            'continent' => $this->continent,
        ];
    }
}
