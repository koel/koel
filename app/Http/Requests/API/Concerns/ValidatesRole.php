<?php

namespace App\Http\Requests\API\Concerns;

use App\Enums\Acl\Role;
use App\Rules\AvailableRole;
use App\Rules\UserCanManageRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait ValidatesRole
{
    /** @return array<string|Enum|ValidationRule> */
    private function roleRule(): array
    {
        return [
            'required',
            Rule::enum(Role::class),
            new AvailableRole(),
            new UserCanManageRole($this->user()),
        ];
    }
}
