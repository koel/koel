<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInteractionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Schema::create('interactions', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('user_id')->unsigned();
            $table->string('song_id', 32);
            $table->boolean('liked')->default(false);
            $table->integer('play_count')->default(0);
            $table->timestamps();
        });

        Schema::table('interactions', static function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('song_id')->references('id')->on('songs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     */
    public function down(): void
    {
        Schema::drop('interactions');
    }
}
