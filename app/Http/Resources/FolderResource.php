<?php

namespace App\Http\Resources;

use App\Models\Folder;
use App\Models\User;
use App\Repositories\UserRepository;
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

        /** @var ?User $uploader */
        $uploader = app(UserRepository::class)->findOneBy(['id' => $this->folder->uploader_id]);

        return $uploader ? "Uploads by {$uploader->name}" : 'Uploads by deleted user';
    }
}
