<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;

class ConvertUserPreferencesFromArrayToJson extends Migration
{
    public function up(): void
    {
        User::all()->each(static function (User $user): void {
            attempt(static function () use ($user): void {
                $preferences = unserialize($user->getRawOriginal('preferences'));
                $user->preferences->lastFmSessionKey = Arr::get($preferences, 'lastfm_session_key');
                $user->save();
            }, false);
        });
    }
}
