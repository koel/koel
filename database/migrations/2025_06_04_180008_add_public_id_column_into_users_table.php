<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('public_id', 36)->unique()->nullable()->after('id');
        });

        DB::table('users')
            ->whereNull('public_id')
            ->orWhere('public_id', '')
            ->get()
            ->each(static function ($user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['public_id' => Str::uuid()->toString()]);
            });
    }
};
