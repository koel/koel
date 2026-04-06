<?php

namespace App\Http\Resources;

use App\Models\DuplicateUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DuplicateUploadResource extends JsonResource
{
    public const array JSON_STRUCTURE = ['type', 'id', 'song_title', 'artist_name', 'filename', 'created_at'];

    public function __construct(
        private readonly DuplicateUpload $upload,
    ) {
        parent::__construct($upload);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'duplicate-uploads',
            'id' => $this->upload->id,
            'song_title' => $this->upload->existingSong?->title,
            'artist_name' => $this->upload->existingSong?->artist_name,
            'filename' => basename($this->upload->location),
            'created_at' => $this->upload->created_at,
        ];
    }
}
