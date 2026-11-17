<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use Illuminate\Validation\Rule;

/**
 * @property-read ?string $api_key
 */
class UpdateYouTubeSettingsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'api_key' => [Rule::nullable(), Rule::string()->max(500)],
        ];
    }
}
