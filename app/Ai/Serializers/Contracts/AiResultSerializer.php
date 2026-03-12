<?php

namespace App\Ai\Serializers\Contracts;

use App\Ai\AiAssistantResult;

interface AiResultSerializer
{
    static function supports(AiAssistantResult $result): bool;

    static function serialize(AiAssistantResult $result): array;
}
