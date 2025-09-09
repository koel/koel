<?php

namespace App\Http\Resources;

use App\Values\GenreSummary;
use Illuminate\Http\Resources\Json\JsonResource;

class GenreResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
    ];

    public function __construct(private readonly GenreSummary $summary)
    {
        parent::__construct($summary);
    }

    /** @inheritdoc */
    public function toArray($request): array
    {
        return [
            'type' => 'genres',
            'id' => $this->summary->publicId,
            'name' => $this->summary->name,
            'song_count' => $this->summary->songCount,
            'length' => $this->summary->length,
        ];
    }
}
