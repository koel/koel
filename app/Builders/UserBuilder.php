<?php

namespace App\Builders;

use App\Enums\Acl\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @extends Builder<User>
 *
 * @method Builder whereRole(RoleEnum|string|array|BaseCollection $roles) Scope the model query to certain roles only.
 */
class UserBuilder extends Builder {}
