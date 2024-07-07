<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', static function (Blueprint $table): void {
            $table->id();
            $table->text('key');
            $table->string('hash')->nullable()->unique();
            $table->json('instance')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
};
