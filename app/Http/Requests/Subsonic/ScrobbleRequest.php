<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property list<string> $id
 * @property bool $submission
 * @property-read int|null $time
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
            'id' => [Rule::required(), Rule::array(), 'min:1'],
            'id.*' => [Rule::string()],
            'time' => [Rule::sometimes(), Rule::numeric()->integer()],
        ];
    }
}
