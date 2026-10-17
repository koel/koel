<?php

namespace App\Http\Requests\API\Album;

use App\Http\Requests\API\Concerns\ValidatesOptionalImage;
use App\Http\Requests\API\Request;
use App\Values\Album\AlbumUpdateData;
use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read ?int $year
 * @property-read ?string $cover
 * @property-read ?string $description
 */
class AlbumUpdateRequest extends Request
{
    use ValidatesOptionalImage;

    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'name' => [Rule::string(), Rule::required()],
            'year' => [Rule::numeric()->integer(), Rule::nullable()],
            'cover' => self::optionalImageRules(),
            'description' => [Rule::string()->max(16_000), Rule::sometimes(), Rule::nullable()],
        ];
    }

    public function toDto(): AlbumUpdateData
    {
        return AlbumUpdateData::make(
            name: $this->name,
            year: $this->year ?: null,
            cover: $this->optionalImageInput('cover'),
            description: $this->has('description') ? (string) $this->description : null,
        );
    }
}
