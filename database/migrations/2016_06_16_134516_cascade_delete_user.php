<?php

use Illuminate\Database\Migrations\Migration;

class CascadeDeleteUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('playlists', function ($table) {
            $table->dropForeign('playlists_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('playlists', function ($table) {
            $table->dropForeign('playlists_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
}
