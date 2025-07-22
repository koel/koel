<?php

namespace App\Models\Contracts;

interface Permissionable
{
    public static function getPermissionableIdentifier(): string;
}
