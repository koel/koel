<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('invitation_token', 36)->nullable()->index();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
            $table->unsignedInteger('invited_by_id')->nullable();
            $table->foreign('invited_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
