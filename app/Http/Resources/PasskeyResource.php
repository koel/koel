<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Passkeys\Passkey;

class PasskeyResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'authenticator',
        'last_used_at',
        'created_at',
    ];

    public function __construct(
        private readonly Passkey $passkey,
    ) {
        parent::__construct($passkey);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'passkeys',
            'id' => $this->passkey->id,
            'name' => $this->passkey->name,
            'authenticator' => $this->passkey->authenticator,
            'last_used_at' => $this->passkey->last_used_at,
            'created_at' => $this->passkey->created_at,
        ];
    }
}
