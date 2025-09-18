<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embeds', static function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->unsignedInteger('user_id');
            $table->string('embeddable_id', 36)->index();
            $table->string('embeddable_type');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
