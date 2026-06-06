<?php

use App\Helpers\Uuid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('subsonic_api_key', 36)->nullable()->unique();
        });

        DB::table('users')
            ->whereNull('subsonic_api_key')
            ->orderBy('id')
            ->each(static function (object $row): void {
                DB::table('users')->where('id', $row->id)->update(['subsonic_api_key' => Uuid::generate()]);
            });
    }
};
