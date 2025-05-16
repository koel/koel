<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourcePermissionResource extends JsonResource
{
    public function __construct(
        private readonly string $type,
        private readonly string|int $id,
        private readonly string $action,
        private readonly bool $allowed
    ) {
        parent::__construct($type);
    }

    /** @inheritDoc */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'resource-permissions',
            'allowed' => $this->allowed,
            'context' => [
                'type' => $this->type,
                'id' => $this->id,
                'action' => $this->action,
            ],
        ];
    }
}
