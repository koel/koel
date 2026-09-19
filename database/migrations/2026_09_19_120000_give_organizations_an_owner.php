<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', static function (Blueprint $table): void {
            $table->unsignedInteger('owner_id')->nullable()->after('id')->index();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
        });

        DB::table('organizations')->update([
            'owner_id' => DB::table('users')->orderBy('id')->value('id'),
        ]);

        Schema::table('organizations', static function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn(['name', 'slug']);
        });
    }
};
