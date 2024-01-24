<?php

namespace App\Http\Requests\API\PlaylistCollaboration;

use App\Http\Requests\API\Request;

/**
 * @property-read int $collaborator
 */
class PlaylistCollaboratorDestroyRequest extends Request
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'collaborator' => 'required|exists:users,id',
        ];
    }
}
