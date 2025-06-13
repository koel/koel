<?php

namespace App\Http\Requests\API\PlaylistCollaboration;

use App\Http\Requests\API\Request;

/**
 * @property-read string $collaborator The public ID of the user to remove as a collaborator.
 */
class PlaylistCollaboratorDestroyRequest extends Request
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'collaborator' => 'required|exists:users,public_id',
        ];
    }
}
