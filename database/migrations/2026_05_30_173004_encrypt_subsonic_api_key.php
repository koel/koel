<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropUnique(['subsonic_api_key']);
            $table->text('subsonic_api_key')->nullable()->change();
            $table->string('subsonic_api_key_hash', 64)->nullable()->after('subsonic_api_key');
        });

        $pepper = (string) config('app.key');

        DB::table('users')
            ->whereNotNull('subsonic_api_key')
            ->orderBy('id')
            ->each(static function (object $row) use ($pepper): void {
                $plaintext = $row->subsonic_api_key;

                DB::table('users')
                    ->where('id', $row->id)
                    ->update([
                        'subsonic_api_key' => Crypt::encryptString($plaintext),
                        'subsonic_api_key_hash' => hash_hmac('sha256', $plaintext, $pepper),
                    ]);
            });

        Schema::table('users', static function (Blueprint $table): void {
            $table->unique('subsonic_api_key_hash');
        });
    }
};
