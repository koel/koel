<?php

namespace App\Http\Requests\API;

use App\Models\DuplicateUpload;
use Illuminate\Validation\Rule;

/**
 * @property array<string> $uploads
 */
class DuplicateUploadRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'uploads' => ['required', 'array', Rule::exists(DuplicateUpload::class, 'id')],
        ];
    }
}
