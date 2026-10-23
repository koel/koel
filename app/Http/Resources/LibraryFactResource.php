<?php

namespace App\Http\Resources;

use App\Values\Statistics\LibraryFact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryFactResource extends JsonResource
{
    public const array JSON_STRUCTURE = ['key', 'label', 'value', 'unit', 'used', 'limit'];

    public function __construct(
        private readonly LibraryFact $fact,
    ) {
        parent::__construct($fact);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->fact->key,
            'label' => $this->fact->label,
            'value' => $this->fact->value,
            'unit' => $this->fact->unit->value,
            'used' => $this->fact->used,
            'limit' => $this->fact->limit,
        ];
    }
}
