<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;

class PlaylistCollaborationTokenExpiredException extends RuntimeException implements ShouldntReport
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => 'The collaboration invite has expired.'], Response::HTTP_NOT_FOUND);
    }
}
