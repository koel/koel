<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organization_scoped_settings', static function (Blueprint $table): void {
            $table->id();
            $table->string('organization_id', 26)->nullable();
            $table->string('key');
            $table->text('value');

            $table->unique(['organization_id', 'key'], 'settings_organization_id_key_unique');

            $table
                ->foreign('organization_id', 'settings_organization_id_foreign')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();
        });

        DB::table('settings')
            ->orderBy('key')
            ->each(static function (object $setting): void {
                DB::table('organization_scoped_settings')->insert([
                    'key' => $setting->key,
                    'value' => $setting->value,
                ]);
            });

        Schema::drop('settings');
        Schema::rename('organization_scoped_settings', 'settings');
    }
};
