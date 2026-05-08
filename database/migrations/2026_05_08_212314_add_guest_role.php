<?php

use App\Enums\Acl\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role as RoleModel;

return new class extends Migration {
    public function up(): void
    {
        RoleModel::findOrCreate(Role::GUEST->value);
    }
};
