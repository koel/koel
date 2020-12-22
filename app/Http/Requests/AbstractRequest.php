<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<mixed> */
    public function rules(): array
    {
        return [];
    }
}
