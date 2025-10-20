<?php

namespace App\Http\Resources;

use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThemeResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'thumbnail_color',
        'thumbnail_image',
        'properties' => [
            '--color-fg',
            '--color-bg',
            '--color-highlight',
            '--font-family',
            '--font-size',
            '--bg-image',
        ],
        'is_custom',
    ];

    public function __construct(private readonly Theme $theme)
    {
        parent::__construct($theme);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'themes',
            'id' => $this->theme->id,
            'name' => $this->theme->name,
            'thumbnail_color' => $this->theme->properties->bgColor,
            'thumbnail_image' => $this->theme->thumbnail ? image_storage_url($this->theme->thumbnail) : '',
            'properties' => $this->theme->properties->toArray(),
            'is_custom' => true, // as opposed to built-in themes
        ];
    }
}
