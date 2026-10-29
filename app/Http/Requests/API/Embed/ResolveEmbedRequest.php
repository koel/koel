<?php

namespace App\Http\Requests\API\Embed;

use App\Enums\EmbeddableType;
use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

/**
 * @property-read ?string $embeddable_id
 * @property-read string $embeddable_type
 */
class ResolveEmbedRequest extends Request
{
    /** @inheritdoc  */
    public function rules(): array
    {
        return [
            'embeddable_id' => [
                Rule::requiredIf(fn (): bool => $this->embeddable_type !== EmbeddableType::FAVORITES->value),
                'nullable',
                Rule::string(),
            ],
            'embeddable_type' => [Rule::required(), Rule::enum(EmbeddableType::class)],
        ];
    }
}
