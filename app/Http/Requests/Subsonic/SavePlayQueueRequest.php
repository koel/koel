<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property array<string> $id
 * @property ?string $current
 * @property int $position
 * @property ?string $c
 */
class SavePlayQueueRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id' => ['array'],
            'id.*' => ['string'],
            'current' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'c' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => self::asStringList($this->input('id', [])),
        ]);
    }

    protected function passedValidation(): void
    {
        $this->merge(['position' => (int) ($this->input('position') ?? 0)]);
    }

    /** @return array<string> */
    private static function asStringList(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return is_string($value) && $value !== '' ? [$value] : [];
    }
}
