<?php

namespace App\Builders;

use App\Enums\Acl\Role as RoleEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @method Builder whereRole(RoleEnum|string|array|BaseCollection $roles) Scope the model query to certain roles only.
 */
class UserBuilder extends Builder
{
}
