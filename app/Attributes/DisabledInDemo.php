<?php

namespace App\Attributes;

use Attribute;
use Illuminate\Http\Response;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class DisabledInDemo
{
    public function __construct(public int $code = Response::HTTP_FORBIDDEN)
    {
    }
}
