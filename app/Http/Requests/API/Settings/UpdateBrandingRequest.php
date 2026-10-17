<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use App\Models\User;
use App\Rules\ValidImageData;
use App\Services\SettingService;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read ?string $logo
 * @property-read ?string $cover
 */
class UpdateBrandingRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => [Rule::required(), Rule::string()],
            'logo' => [Rule::sometimes(), Rule::nullable(), Rule::string()],
            'cover' => [Rule::sometimes(), Rule::nullable(), Rule::string()],
        ];
    }

    public function validator(Factory $factory, SettingService $settingService): Validator
    {
        $validator = $this->createDefaultValidator($factory);
        /** @var User $user */
        $user = $this->user();
        $currentBranding = $settingService->getBranding($user->organization);

        $validator->sometimes(
            'logo',
            [new ValidImageData()],
            static fn (Fluent $input): bool => $input->get('logo') !== $currentBranding->logo,
        );

        $validator->sometimes(
            'cover',
            [new ValidImageData()],
            static fn (Fluent $input): bool => $input->get('cover') !== $currentBranding->cover,
        );

        return $validator;
    }
}
