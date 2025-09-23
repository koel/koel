<?php

use App\Enums\Acl\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // @phpstan-ignore-next-line
        User::query()->where('is_admin', true)->each(static function (User $user): void {
            $user->syncRoles(Role::ADMIN);
        });

        Schema::table('users', static function (Blueprint $table): void {
            $table->dropColumn('is_admin');
        });
    }
};
