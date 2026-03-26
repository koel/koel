<?php

namespace App\Http\Resources;

use App\Models\DuplicateUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DuplicateUploadResource extends JsonResource
{
    public const JSON_STRUCTURE = ['type', 'id', 'existing_song', 'filename', 'created_at'];

    public function __construct(private readonly DuplicateUpload $upload)
    {
        parent::__construct($upload);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'duplicate-uploads',
            'id' => $this->upload->id,
            'existing_song' => $this->upload->existingSong
                ? new SongResource($this->upload->existingSong)
                : null,
            'filename' => basename($this->upload->location),
            'created_at' => $this->upload->created_at,
        ];
    }
}
