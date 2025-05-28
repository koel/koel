<?php

namespace App\Http\Resources;

use App\Models\Folder;
use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'path',
        'name',
    ];

    public function __construct(private readonly Folder $folder)
    {
        parent::__construct($this->folder);
    }

    /** @inheritdoc */
    public function toArray($request): array
    {
        return [
            'type' => 'folders',
            'id' => $this->folder->id,
            'path' => $this->folder->path,
            'name' => $this->folder->name,
        ];
    }
}
