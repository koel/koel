<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

/**
 * @property int $count
 */
class GetNewestPodcastsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'count' => [Rule::numeric()->integer()->min(1)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'count' => (int) $this->input('count', 20),
        ]);
    }
}
