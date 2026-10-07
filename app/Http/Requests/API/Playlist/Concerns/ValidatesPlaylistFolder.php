<?php

namespace App\Http\Requests\API\Playlist\Concerns;

use App\Models\PlaylistFolder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait ValidatesPlaylistFolder
{
    /**
     * @return array{folder_id: array<string|Exists>, folder_name: array<string>}
     */
    private function playlistFolderRules(): array
    {
        return [
            'folder_id' => [
                'nullable',
                'sometimes',
                'prohibits:folder_name',
                Rule::exists(PlaylistFolder::class, 'id')->where('user_id', $this->user()->id),
            ],
            'folder_name' => ['nullable', 'sometimes', 'prohibits:folder_id', 'string', 'max:191'],
        ];
    }
}
