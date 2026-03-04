<?php

namespace App\Http\Requests\API\Embed;

use App\Http\Requests\API\Request;

/**
 * @property-read string $theme
 * @property-read string $layout
 * @property-read bool $preview
 */
class EmbedOptionsEncryptRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            // we can't really validate the theme id, but we'll make sure it's not too long
            'theme' => 'required|string|max:32',
            'layout' => 'required|string|in:full,compact',
            'preview' => 'sometimes|bool',
        ];
    }
}
