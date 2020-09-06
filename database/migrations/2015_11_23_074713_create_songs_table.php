<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSongsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->integer('album_id')->unsigned();
            $table->string('title');
            $table->float('length');
            $table->text('lyrics');
            $table->text('path');
            $table->integer('mtime');
            $table->timestamps();
        });

        Schema::table('songs', function (Blueprint $table) {
            $table->foreign('album_id')->references('id')->on('albums');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('songs');
    }
}
