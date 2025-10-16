<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', static function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->unsignedInteger('user_id')->index();
            $table->string('name');
            $table->string('thumbnail')->nullable();
            $table->text('properties');
            $table->timestamps();

            $table->foreign('user_id')->on('users')->references('id')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
