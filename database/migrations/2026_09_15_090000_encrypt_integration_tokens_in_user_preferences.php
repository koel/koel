<?php

use App\Values\User\UserPreferences;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $secretKeys = UserPreferences::encryptedKeys();

        DB::table('users')
            ->select(['id', 'preferences'])
            ->lazyById()
            ->each(static function (object $user) use ($secretKeys): void {
                $preferences = json_decode($user->preferences ?? '', true);

                if (!is_array($preferences)) {
                    return;
                }

                $encrypted = false;

                foreach ($secretKeys as $secretKey) {
                    if (is_string(Arr::get($preferences, $secretKey))) {
                        $preferences[$secretKey] = Crypt::encryptString($preferences[$secretKey]);
                        $encrypted = true;
                    }
                }

                if ($encrypted) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['preferences' => json_encode($preferences)]);
                }
            });
    }
};
