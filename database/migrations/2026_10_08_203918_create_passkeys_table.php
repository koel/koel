<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('passkeys', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->string('name');
            $table->string('credential_id')->unique();
            $table->json('credential');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
