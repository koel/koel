<?php

namespace App\Http\Requests\API\Radio;

use App\Http\Requests\API\Request;
use App\Rules\ValidImageData;
use App\Rules\ValidRadioStationUrl;
use App\Values\Radio\RadioStationUpdateData;
use Illuminate\Validation\Rule;

/**
 * @property-read string $url
 * @property-read string $name
 * @property-read ?string $logo
 * @property-read ?string $description
 * @property-read ?bool $is_public
 */
class RadioStationUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'url',
                Rule::unique('radio_stations')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id);
                })->ignore($this->route('station')->id), // @phpstan-ignore-line
                app(ValidRadioStationUrl::class),
            ],
            'name' => ['required', 'string', 'max:191'],
            'logo' => ['nullable', new ValidImageData()],
            'description' => ['string'],
            'is_public' => ['boolean'],
        ];
    }

    public function toDto(): RadioStationUpdateData
    {
        return RadioStationUpdateData::make(
            name: $this->name,
            url: $this->url,
            description: $this->description,
            logo: $this->logo,
            isPublic: $this->boolean('is_public')
        );
    }
}
