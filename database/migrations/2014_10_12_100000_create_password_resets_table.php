<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePasswordResetsTable extends Migration
{
    public function up(): void
    {
        Schema::create('password_resets', static function (Blueprint $table): void {
            $table->string('email')->index();
            $table->string('token')->index();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::drop('password_resets');
    }
}
