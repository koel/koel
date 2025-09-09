<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [];
    }
}
