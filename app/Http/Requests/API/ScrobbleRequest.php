<?php

namespace App\Http\Requests\API;

/**
 * @property int $timestamp
 */
class ScrobbleRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return ['timestamp' => 'required|numeric'];
    }
}
