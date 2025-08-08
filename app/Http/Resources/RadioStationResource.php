<?php

namespace App\Http\Resources;

use App\Models\RadioStation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RadioStationResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'name',
        'id',
        'url',
        'logo',
        'description',
        'is_public',
        'created_at',
    ];

    public function __construct(private readonly RadioStation $station)
    {
        parent::__construct($station);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'radio-stations',
            'name' => $this->station->name,
            'id' => $this->station->id,
            'url' => $this->station->url,
            'logo' => $this->station->logo,
            'description' => $this->station->description,
            'is_public' => $this->station->is_public,
            'created_at' => $this->station->created_at,
            'favorite' => $this->station->favorite,
        ];
    }
}
