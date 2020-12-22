<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlbumsTable extends Migration
{
    public function up(): void
    {
        Schema::create('albums', static function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('artist_id')->unsigned();
            $table->string('name');
            $table->string('cover')->default('');
            $table->timestamps();
        });

        Schema::table('albums', static function (Blueprint $table): void {
            $table->foreign('artist_id')->references('id')->on('artists')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::drop('albums');
    }
}
