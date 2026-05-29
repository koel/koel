<?php

use App\Helpers\Uuid;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('subsonic_api_key', 36)->nullable()->unique();
        });

        User::query()
            ->whereNull('subsonic_api_key')
            ->each(static function (User $user): void {
                $user->forceFill(['subsonic_api_key' => Uuid::generate()])->saveQuietly();
            });
    }
};
