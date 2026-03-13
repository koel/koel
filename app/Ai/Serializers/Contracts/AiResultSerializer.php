<?php

namespace App\Ai\Serializers\Contracts;

use App\Ai\AiAssistantResult;

interface AiResultSerializer
{
    public static function supports(AiAssistantResult $result): bool;

    public static function serialize(AiAssistantResult $result): array;
}
