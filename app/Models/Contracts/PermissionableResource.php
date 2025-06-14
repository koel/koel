<?php

namespace App\Models\Contracts;

interface PermissionableResource
{
    public static function getPermissionableResourceIdentifier(): string;
}
