<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property ?string $id
 * @property bool $includeEpisodes
 */
class GetPodcastsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'string'],
            'includeEpisodes' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'includeEpisodes' => filter_var($this->input('includeEpisodes', true), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
