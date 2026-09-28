<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use App\Models\User;
use App\Services\SettingService;
use App\Values\AiSettings;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Laravel\Ai\Enums\Lab;

/**
 * @property-read bool $enabled
 * @property-read string $provider
 * @property-read ?string $api_key
 */
class UpdateAiSettingsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'provider' => ['required', Rule::enum(Lab::class)->only(AiSettings::PROVIDERS)],
            'api_key' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function validator(Factory $factory, SettingService $settingService): Validator
    {
        $validator = $this->createDefaultValidator($factory);

        /** @var User $user */
        $user = $this->user();
        $current = $settingService->getAiSettings($user->organization);

        $validator->sometimes(
            'api_key',
            'required',
            static fn (Fluent $input): bool => (
                (bool) $input->get('enabled')
                && (!$current->apiKey || $input->get('provider') !== $current->provider?->value)
            ),
        );

        return $validator;
    }
}
