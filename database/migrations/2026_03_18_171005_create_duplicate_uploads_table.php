<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('duplicate_uploads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('file_path');
            $table->integer('existing_song_id')->unsigned()->nullable();
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
