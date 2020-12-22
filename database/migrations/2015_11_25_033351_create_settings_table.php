<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSettingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('settings', static function (Blueprint $table): void {
            $table->string('key');
            $table->text('value');
            $table->primary('key');
        });
    }

    public function down(): void
    {
        Schema::drop('settings');
    }
}
