<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use App\Rules\ValidImageData;
use App\Services\SettingService;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Fluent;

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
            'name' => ['required', 'string'],
            'logo' => ['sometimes', 'nullable', 'string'],
            'cover' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function validator(Factory $factory, SettingService $settingService): Validator
    {
        $validator = $this->createDefaultValidator($factory);
        $currentBranding = $settingService->getBranding();

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
