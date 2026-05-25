<?php

namespace App\Http\Resources;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
        'type',
        'id',
        'parent_id',
        'name',
        'is_uploads',
    ];

    public function __construct(
        private readonly Folder $folder,
    ) {
        parent::__construct($this->folder);
    }

    /** @inheritdoc */
    public function toArray($request): array
    {
        return [
            'type' => 'folders',
            'id' => $this->folder->id,
            'parent_id' => $this->folder->parent_id,
            'name' => $this->resolveDisplayName($request->user()),
            'is_uploads' => $this->folder->is_uploads_folder,
        ];
    }

    private function resolveDisplayName(?User $viewer): string
    {
        if (!$this->folder->is_uploads_folder) {
            return $this->folder->name;
        }

        if ($viewer && $this->folder->uploader_id === $viewer->id) {
            return 'Your uploads';
        }

        return $this->folder->uploader ? "Uploads by {$this->folder->uploader->name}" : 'Uploads by deleted user';
    }
}
