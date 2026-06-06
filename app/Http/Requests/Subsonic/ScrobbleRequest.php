<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Support\Arr;

/**
 * @property list<string> $id
 * @property bool $submission
 */
class ScrobbleRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => Arr::wrap($this->input('id')),
            'submission' => filter_var($this->input('submission', true), FILTER_VALIDATE_BOOL),
        ]);
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id' => ['required', 'array', 'min:1'],
            'id.*' => ['string'],
        ];
    }
}
