<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::all()->each(static function (Setting $setting): void {
            $setting->value = unserialize($setting->getRawOriginal('value'));
            $setting->save();
        });
    }
};
