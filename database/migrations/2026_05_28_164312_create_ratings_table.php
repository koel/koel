<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ratings', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('rateable_id', 36);
            $table->string('rateable_type');
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['user_id', 'rateable_id', 'rateable_type']);
            $table->index(['user_id', 'rateable_type']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
