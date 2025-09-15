<?php

namespace App\Http\Resources;

use App\Values\EmbedOptions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmbedOptionsResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'theme',
        'layout',
        'preview',
    ];

    public function __construct(private readonly EmbedOptions $options)
    {
        parent::__construct($options);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'embed-options',
            'theme' => $this->options->theme,
            'layout' => $this->options->layout,
            'preview' => $this->options->preview,
        ];
    }
}
