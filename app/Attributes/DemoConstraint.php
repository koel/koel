<?php

namespace App\Attributes;

use Illuminate\Http\Response;

abstract class DemoConstraint
{
    public function __construct(
        public int $code = Response::HTTP_FORBIDDEN,
        public bool $allowAdminOverride = true,
    ) {
    }
}
