<?php

use App\Values\User\UserPreferences;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Encryption\Encrypter;
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
                    $token = Arr::get($preferences, $secretKey);

                    if (!is_string($token) || Encrypter::appearsEncrypted($token)) {
                        continue;
                    }

                    $preferences[$secretKey] = Crypt::encryptString($token);
                    $encrypted = true;
                }

                if ($encrypted) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->where('preferences', $user->preferences)
                        ->update(['preferences' => json_encode($preferences)]);
                }
            });
    }
};
