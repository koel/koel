<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ConvertUserPreferencesFromArrayToJson extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->get()
            ->each(static function ($user): void {
                rescue(static function () use ($user): void {
                    $preferences = unserialize($user->preferences);

                    if (!is_array($preferences)) {
                        $preferences = [];
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['preferences' => json_encode($preferences)]);
                });
            });
    }
}
