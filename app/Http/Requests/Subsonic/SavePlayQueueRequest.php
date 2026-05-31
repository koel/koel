<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property array<string> $id
 * @property ?string $current
 * @property int $position
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
            'position' => ['integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => self::asStringList($this->input('id', [])),
            'position' => (int) $this->input('position', 0),
        ]);
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
