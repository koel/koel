<?php

namespace App\Http\Requests\API\Radio;

use App\Http\Requests\API\Request;
use App\Rules\ValidImageData;
use App\Rules\ValidRadioStationUrl;
use App\Values\Radio\RadioStationCreateData;
use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read string $url
 * @property-read ?string $logo
 * @property-read ?string $description
 * @property-read ?bool $is_public
 */
class RadioStationStoreRequest extends Request
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
                }),
                app(ValidRadioStationUrl::class),
            ],
            'name' => ['required', 'string', 'max:191'],
            'logo' => ['nullable', new ValidImageData()],
            'description' => ['nullable', 'string'],
            'is_public' => ['boolean', 'nullable'],
        ];
    }

    public function toDto(): RadioStationCreateData
    {
        return RadioStationCreateData::make(
            url: $this->url,
            name: $this->name,
            description: $this->description,
            logo: $this->logo,
            isPublic: $this->is_public,
        );
    }
}
