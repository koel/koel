<?php

namespace App\Http\Requests\API;

/**
 * @property-read array $sso_user
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
            'sso_user' => 'required|array',
            'sso_user.provider' => 'required|string',
            'sso_user.id' => 'required|string',
            'sso_user.email' => 'required|email',
            'sso_user.name' => 'required|string',
            'sso_user.avatar' => 'nullable|string',
            'terms_accepted' => 'required|accepted',
            'privacy_accepted' => 'required|accepted',
            'age_verified' => 'required|accepted',
        ];
    }
}
