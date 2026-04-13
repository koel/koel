<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $id_token
 * @property-read bool $terms_accepted
 * @property-read bool $privacy_accepted
 * @property-read bool $age_verified
 */
class GoogleMobileConsentRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id_token' => 'required|string',
            'terms_accepted' => 'required|accepted',
            'privacy_accepted' => 'required|accepted',
            'age_verified' => 'required|accepted',
        ];
    }
}
