<?php

namespace App\Values;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * A Lemon Squeezy license instance
 * @see https://docs.lemonsqueezy.com/api/license-key-instances#the-license-key-instance-object
 */
final class LicenseInstance implements Arrayable, Jsonable
{
    private function __construct(public string $id, public string $name, public Carbon $createdAt)
    {
    }

    public static function make(string $id, string $name, Carbon|string $createdAt): self
    {
        return new self($id, $name, is_string($createdAt) ? Carbon::parse($createdAt) : $createdAt);
    }

    public static function fromJsonObject(object $json): self
    {
        return new self(
            id: $json->id,
            name: $json->name,
            createdAt: Carbon::parse($json->created_at),
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
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
