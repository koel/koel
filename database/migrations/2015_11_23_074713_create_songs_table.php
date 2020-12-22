<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSongsTable extends Migration
{
    public function up(): void
    {
        Schema::create('songs', static function (Blueprint $table): void {
            $table->string('id', 32)->primary();
            $table->integer('album_id')->unsigned();
            $table->string('title');
            $table->float('length');
            $table->text('lyrics');
            $table->text('path');
            $table->integer('mtime');
            $table->timestamps();
        });

        Schema::table('songs', static function (Blueprint $table): void {
            $table->foreign('album_id')->references('id')->on('albums');
        });
    }

    public function down(): void
    {
        Schema::drop('songs');
    }
}
