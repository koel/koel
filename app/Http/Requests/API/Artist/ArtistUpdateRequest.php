<?php

namespace App\Http\Requests\API\Artist;

use App\Http\Requests\API\Concerns\ValidatesOptionalImage;
use App\Http\Requests\API\Request;
use App\Values\Artist\ArtistUpdateData;
use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read ?string $image
 * @property-read ?string $description
 */
class ArtistUpdateRequest extends Request
{
    use ValidatesOptionalImage;

    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'name' => [Rule::string(), Rule::required()],
            'image' => self::optionalImageRules(),
            'description' => [Rule::string()->max(16_000), Rule::sometimes(), Rule::nullable()],
        ];
    }

    public function toDto(): ArtistUpdateData
    {
        return ArtistUpdateData::make(
            name: $this->name,
            image: $this->optionalImageInput('image'),
            description: $this->has('description') ? (string) $this->description : null,
        );
    }
}
