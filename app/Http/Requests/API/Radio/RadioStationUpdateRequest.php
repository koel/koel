<?php

namespace App\Http\Requests\API\Radio;

use App\Http\Requests\API\Concerns\ValidatesOptionalImage;
use App\Http\Requests\API\Radio\Concerns\ValidatesRadioStationUrl;
use App\Http\Requests\API\Request;
use App\Models\RadioStation;
use App\Values\Radio\RadioStationUpdateData;

/**
 * @property-read string $url
 * @property-read string $name
 * @property-read ?string $logo
 * @property-read ?string $description
 * @property-read ?bool $is_public
 * @property-read ?string $homepage_url
 */
class RadioStationUpdateRequest extends Request
{
    use ValidatesRadioStationUrl;
    use ValidatesOptionalImage;

    /** @inheritdoc */
    public function rules(): array
    {
        /** @var RadioStation $station */
        $station = $this->route('station');

        return [
            'url' => $this->radioStationUrlRules($station),
            'name' => ['required', 'string', 'max:191'],
            'logo' => self::optionalImageRules(),
            'description' => ['string', 'sometimes', 'nullable'],
            'is_public' => ['boolean'],
            'homepage_url' => ['sometimes', 'nullable', 'url'],
        ];
    }

    public function toDto(): RadioStationUpdateData
    {
        return RadioStationUpdateData::make(
            name: $this->name,
            url: $this->url,
            description: $this->string('description'),
            logo: $this->optionalImageInput('logo'),
            isPublic: $this->boolean('is_public'),
            homepageUrl: $this->homepage_url,
        );
    }
}
