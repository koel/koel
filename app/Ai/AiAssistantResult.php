<?php

namespace App\Ai;

class AiAssistantResult
{
    public ?string $action = null;

    /** @var array<string, mixed> */
    public array $data = [];
}
