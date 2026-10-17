<?php

namespace App\Http\Requests\API\Radio;

use App\Http\Requests\API\Radio\Concerns\ValidatesRadioStationUrl;
use App\Http\Requests\API\Request;
use App\Rules\ValidImageData;
use App\Values\Radio\RadioStationCreateData;
use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read string $url
 * @property-read ?string $logo
 * @property-read ?string $description
 * @property-read ?bool $is_public
 * @property-read ?string $homepage_url
 */
class RadioStationStoreRequest extends Request
{
    use ValidatesRadioStationUrl;

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'url' => $this->radioStationUrlRules(),
            'name' => [Rule::required(), Rule::string()->max(191)],
            'logo' => [Rule::nullable(), new ValidImageData()],
            'description' => [Rule::sometimes(), Rule::nullable(), Rule::string()],
            'is_public' => [Rule::boolean(), Rule::nullable()],
            'homepage_url' => [Rule::sometimes(), Rule::nullable(), Rule::url()],
        ];
    }

    public function toDto(): RadioStationCreateData
    {
        return RadioStationCreateData::make(
            url: $this->url,
            name: $this->name,
            description: $this->string('description'),
            logo: $this->logo,
            isPublic: $this->is_public,
            homepageUrl: $this->homepage_url,
        );
    }
}
