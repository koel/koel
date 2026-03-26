<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('duplicate_uploads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('user_id')->unsigned();
            $table->string('existing_song_id')->nullable();
            $table->string('location');
            $table->string('storage');
            $table->boolean('make_public');
            $table->boolean('extract_folder_structure');
            $table->timestamps();
        });

        Schema::table('duplicate_uploads', static function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('existing_song_id')->references('id')->on('songs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_uploads');
    }
};
