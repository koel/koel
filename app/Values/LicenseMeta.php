<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * A Lemon Squeezy license meta
 * @see https://docs.lemonsqueezy.com/help/licensing/license-api#meta
 */
final class LicenseMeta implements Arrayable, Jsonable
{
    private function __construct(public int $customerId, public string $customerName, public string $customerEmail)
    {
    }

    public static function make(int $customerId, string $customerName, string $customerEmail): self
    {
        return new self($customerId, $customerName, $customerEmail);
    }

    public static function fromJsonObject(object $json): self
    {
        return new self(
            customerId: $json->customer_id,
            customerName: $json->customer_name,
            customerEmail: $json->customer_email,
        );
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
        ];
    }
}
