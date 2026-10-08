<?php

namespace App\Http\Requests\API\Auth\Passkey\Concerns;

use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Support\WebAuthn;
use Throwable;
use Webauthn\PublicKeyCredential;

trait CarriesPasskeyCredential
{
    /** @return array<string, array<string>> */
    private static function credentialRules(): array
    {
        return [
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
        ];
    }

    public function credential(): PublicKeyCredential
    {
        try {
            return WebAuthn::fromJson(json_encode($this->input('credential')) ?: '{}', PublicKeyCredential::class);
        } catch (Throwable) {
            throw ValidationException::withMessages(['credential' => 'Invalid passkey credential.']);
        }
    }
}
